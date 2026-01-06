<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2026 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Plugins;
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
    private ProductoVariante $product;
    private mixed $familyRateCache = [];

    public function __construct()
    {
        $this->product = new ProductoVariante();
    }

    /**
     * @param string $idempresa
     * @return DataBaseWhere
     */
    protected function getCompanyDatabaseWhere(string $idempresa): DataBaseWhere
    {
        $almacenes = [];
        foreach (Almacenes::all() as $almacen) {
            if ((string)$almacen->idempresa === $idempresa) {
                $almacenes[] = $almacen->codalmacen;
            }
        }

        return new DataBaseWhere('S.codalmacen', implode(',', $almacenes), 'IN');
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
    public function getStock(string $code): array
    {
        $where = [
            new DataBaseWhere('LOWER(S.referencia)', mb_strtolower($code, 'UTF8'))
        ];

        return (new ProductoStock())->all($where);
    }

    /**
     * Searches products by text, filters, warehouse, or company.
     *
     * @param string $text Search text (barcode, reference, or description)
     * @param array $filters Additional filters (e.g., families, codcliente)
     * @param string $wharehouse Warehouse code filter
     * @param string $company Company ID filter
     * @return array Product list
     */
    public function search(string $text, array $filters = [], string $wharehouse = '', string $company = ''): array
    {
        $where = [
            new DataBaseWhere('V.codbarras', $text, 'LIKE'),
            new DataBaseWhere('V.referencia', $text, 'LIKE', 'OR'),
            new DataBaseWhere('P.descripcion', $text, 'XLIKE', 'OR'),
            new DataBaseWhere('P.sevende', true)
        ];

        if ($company) {
            $where[] = $this->getCompanyDatabaseWhere($company);
        } elseif ($wharehouse) {
            $where[] = new DataBaseWhere('S.codalmacen', $wharehouse);
            $where[] = new DataBaseWhere('S.codalmacen', NULL, 'IS', 'OR');
        }

        if (!empty($filters['families'])) {
            $families = implode(',', array_column($filters['families'], 'code'));
            $where[] = new DataBaseWhere('codfamilia', $families, 'IN');
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
            new DataBaseWhere('V.referencia', $text),
            new DataBaseWhere('V.codbarras', $text, '=', 'OR')
        ];

        $result = $this->product->all($where, [], 0, 1);

        if (empty($result)) return [];

        $model = current($result);
        return [
            'code' => $model->code,
            'description' => $model->description,
            'thumbnail' => $model->thumbnail ?? '',
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
        if (empty($codcliente)) return;

        $rate = $this->getCustomerRate($codcliente);
        if (empty($rate->codtarifa)) return;

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
        if (empty($product->codfamilia)) return null;

        $cacheKey = $product->codfamilia . '-' . $rate->codtarifa;
        return $this->familyRateCache[$cacheKey] ?? null;
    }

    protected function preloadFamilyRates(array $products, Tarifa $rate): void
    {
        $familias = array_unique(array_filter(array_map(fn($p) => $p->codfamilia, $products)));
        if (empty($familias)) return;

        $where = [
            new DataBaseWhere('codfamilia', implode(',', $familias), 'IN'),
            new DataBaseWhere('codtarifa', $rate->codtarifa)
        ];

        $familyRates = new TarifaFamilia()->all($where);

        foreach ($familyRates as $familyRate) {
            $cacheKey = $familyRate->codfamilia . '-' . $familyRate->codtarifa;
            $this->familyRateCache[$cacheKey] = $familyRate;
        }
    }
}
