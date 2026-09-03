<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2026 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\GrupoClientes;
use FacturaScripts\Dinamic\Model\Join\ProductoStock;
use FacturaScripts\Dinamic\Model\Join\ProductoVariante;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Plugins\TarifasAvanzadas\Model\TarifaFamilia;

/**
 * Product service for POS operations.
 * Handles product search, stock management, and pricing.
 *
 * -- Índice en codbarras (búsqueda por barcode)
 * CREATE INDEX idx_variantes_codbarras ON variantes(codbarras);
 *
 * -- Índice en referencia (búsqueda por referencia)
 * CREATE INDEX idx_variantes_referencia ON variantes(referencia);
 *
 * -- Índice fulltext para descripción (búsqueda de texto)
 * CREATE FULLTEXT INDEX idx_productos_descripcion ON productos(descripcion);
 */
class Products
{
    use ExtensionsTrait;

    private ProductoVariante $product;
    private mixed $familyRateCache = [];

    public function __construct()
    {
        $this->product = new ProductoVariante();
    }

    /**
     * @param string $idempresa
     * @return Where
     */
    protected function getCompanyDatabaseWhere(string $idempresa): Where
    {
        $almacenes = [];
        foreach (Almacenes::all() as $almacen) {
            if ((string)$almacen->idempresa === $idempresa) {
                $almacenes[] = $almacen->codalmacen;
            }
        }

        return Where::in('S.codalmacen', implode(',', $almacenes));
    }

    /**
     * Gets product image URLs.
     *
     * @return string[]
     */
    public function getImagesUrl(string $id, string $code): array
    {
        $routes = [];

        foreach (ProductoVariante::getImages($id, $code) as $image) {
            $routes[] = FS_ROUTE . '/' . $image->url('download-permanent');
        }

        return $routes;
    }

    /**
     * Gets stock information for a product.
     *
     * @param string $code Product reference code
     * @return ProductoStock[]
     */
    public function getStock(string $code, string $warehouse = '', string $company = ''): array
    {
        $where = [
            Where::eq('LOWER(S.referencia)', mb_strtolower($code, 'UTF8'))
        ];

        if ($company) {
            $where[] = $this->getCompanyDatabaseWhere($company);
        } elseif ($warehouse) {
            $where[] = Where::eq('S.codalmacen', $warehouse);
        }

        return ProductoStock::all($where);
    }

    /**
     * Gets the complete product information required by the POS detail modal.
     */
    public function getDetail(
        string $code,
        string $customerCode = '',
        string $warehouse = '',
        string $company = ''
    ): array
    {
        $where = [
            Where::eq('V.referencia', $code),
        ];

        if ($company) {
            $where[] = $this->getCompanyDatabaseWhere($company);
        } elseif ($warehouse) {
            $where[] = Where::eq('S.codalmacen', $warehouse);
            $where[] = Where::orIsNull('S.codalmacen');
        }

        $products = $this->product->all($where, [], 0, 1);

        if (empty($products)) {
            return [];
        }

        $this->applyProductRates($products, ['codcliente' => $customerCode]);
        $product = current($products);

        return [
            'product' => $product->toArray(),
            'images' => $this->getImagesUrl((string)$product->id, $product->code),
            'stocks' => $this->getStock($product->code, $warehouse, $company),
        ];
    }

    /**
     * Searches products by text, filters, warehouse, or company.
     *
     * @param string $text Search text (barcode, reference, or description)
     * @param array $filters Additional filters (e.g., families, codcliente)
     * @param string $warehouse Warehouse code filter
     * @param string $company Company ID filter
     * @return array Product list
     */
    public function search(string $text, array $filters = [], string $warehouse = '', string $company = ''): array
    {
        $where = [
            Where::like('V.codbarras', $text),
            Where::orLike('V.referencia', $text),
            Where::orXlike('P.descripcion', $text)
        ];

        if (Plugins::isEnabled('SKU')) {
            $where[] = Where::orLike('P.referencia_fabricante', $text);
        }

        $where[] = Where::eq('P.sevende', true);

        if ($company) {
            $where[] = $this->getCompanyDatabaseWhere($company);
        } elseif ($warehouse) {
            $where[] = Where::eq('S.codalmacen', $warehouse);
            $where[] = Where::orIsNull('S.codalmacen');
        }

        if (!empty($filters['families'])) {
            $families = implode(',', array_column($filters['families'], 'code'));
            $where[] = Where::in('P.codfamilia', $families);
        }

        $products = $this->product->all($where, [], 0, 30);

        $this->applyProductRates($products, $filters);

        return $products;
    }

    /**
     * Searches product by barcode.
     *
     * @param string $text Barcode to search
     * @return array Product found or false
     */
    public function searchBarcode(string $text): array
    {
        $where = [
            Where::eq('V.referencia', $text),
            Where::orEq('V.codbarras', $text)
        ];

        $result = $this->product->all($where, [], 0, 1);

        if (empty($result)) {
            return [];
        }

        $model = current($result);
        return [
            'code' => $model->code,
            'description' => $model->description,
            'thumbnail' => $model->thumbnail ?? '',
            'bloqueado' => $model->bloqueado ?? false,
        ];
    }

    /**
     * Applies customer price rates to products.
     *
     * @param ProductoVariante[] $products Product list
     * @param array $filters Filters including customer code
     */
    private function applyProductRates(array $products, array $filters): void
    {
        $codcliente = $filters['codcliente'] ?? '';
        if (empty($codcliente)) {
            return;
        }

        $rate = $this->getCustomerRate($codcliente);
        if (empty($rate->codtarifa)) {
            return;
        }

        if (Plugins::isEnabled('TarifasAvanzadas')) {
            $this->preloadFamilyRates($products, $rate);
        }

        foreach ($products as $product) {
            $familyRate = $this->getFamilyRate($product, $rate);
            if ($familyRate) {
                $product->applyFamilyRate($familyRate);
                continue;
            }

            $product->applyRate($rate);
        }
    }

    public function getCustomerRate(string $customerCode): Tarifa
    {
        $rate = new Tarifa();
        $customer = new Cliente();

        if ($customer->load($customerCode) && !empty($customer->codtarifa) && $rate->load($customer->codtarifa)) {
            return $rate;
        }

        $group = new GrupoClientes();
        if (!empty($customer->codgrupo) && $group->load($customer->codgrupo) && !empty($group->codtarifa)) {
            $rate->load($group->codtarifa);
        }

        return $rate;
    }

    public function getFamilyRate(ProductoVariante $product, Tarifa $rate): ?TarifaFamilia
    {
        if (empty($product->codfamilia)) {
            return null;
        }

        $cacheKey = $product->codfamilia . '-' . $rate->codtarifa;
        return $this->familyRateCache[$cacheKey] ?? null;
    }

    protected function preloadFamilyRates(array $products, Tarifa $rate): void
    {
        $familias = array_unique(array_filter(array_map(fn($p) => $p->codfamilia, $products)));
        if (empty($familias)) {
            return;
        }

        $where = [
            Where::in('codfamilia', implode(',', $familias)),
            Where::eq('codtarifa', $rate->codtarifa)
        ];

        $familyRates = new TarifaFamilia()->all($where);

        foreach ($familyRates as $familyRate) {
            $cacheKey = $familyRate->codfamilia . '-' . $familyRate->codtarifa;
            $this->familyRateCache[$cacheKey] = $familyRate;
        }
    }
}
