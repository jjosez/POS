<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2026 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\GrupoClientes;
use FacturaScripts\Dinamic\Model\Join\ProductoStock;
use FacturaScripts\Dinamic\Model\Join\ProductoVariante;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Plugins\TarifasAvanzadas\Model\TarifaFamilia;

/**
 * Product service for POS operations.
 * Handles product search, stock management, and pricing.
 *
 * -- Index on codbarras (barcode search)
 * CREATE INDEX idx_variantes_codbarras ON variantes(codbarras);
 *
 * -- Index on referencia (reference search)
 * CREATE INDEX idx_variantes_referencia ON variantes(referencia);
 *
 * -- Fulltext index on descripcion (text search)
 * CREATE FULLTEXT INDEX idx_productos_descripcion ON productos(descripcion);
 */
class Products
{
    use ExtensionsTrait;

    private ProductoVariante $product;
    private mixed $familyRateCache = [];
    private ?TerminalPuntoVenta $terminal;

    public function __construct(?TerminalPuntoVenta $terminal = null)
    {
        $this->product = new ProductoVariante();
        $this->terminal = $terminal;
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
    ): array {
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
        $this->applyTerminalAvailability($products);
        $this->applyLinePriceRounding($products);
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
        $where = [];
        if (trim($text) !== '') {
            $search = [
                Where::like('V.codbarras', $text),
                Where::orLike('V.referencia', $text),
                Where::orXlike('P.descripcion', $text)
            ];

            if (Plugins::isEnabled('SKU')) {
                $search[] = Where::orLike('P.referencia_fabricante', $text);
            }

            $where[] = Where::sub($search);
        }

        if ($company) {
            $where[] = $this->getCompanyDatabaseWhere($company);
        } elseif ($warehouse) {
            $where[] = Where::sub([
                Where::eq('S.codalmacen', $warehouse),
                Where::orIsNull('S.codalmacen'),
            ]);
        }

        $where[] = Where::eq('P.sevende', true);

        if (!empty($filters['families'])) {
            $families = array_column($filters['families'], 'code');

            $where[] = Where::in('P.codfamilia', $families);
        }

        Tools::log('pos')->info(Where::multiSql($where));

        $products = $this->product->all($where, [], 0, 30);

        $this->applyProductRates($products, $filters);
        $this->applyTerminalAvailability($products);
        $this->applyLinePriceRounding($products);

        return $products;
    }

    /**
     * Searches product by barcode.
     *
     * @param string $text Barcode to search
     * @return array Product found or false
     */
    public function searchBarcode(string $text, string $warehouse = '', string $company = ''): array
    {
        $where = [
            Where::eq('V.referencia', $text),
            Where::orEq('V.codbarras', $text)
        ];

        if ($company) {
            $where[] = $this->getCompanyDatabaseWhere($company);
        } elseif ($warehouse) {
            $where[] = Where::sub([
                Where::eq('S.codalmacen', $warehouse),
                Where::orIsNull('S.codalmacen'),
            ]);
        }

        $result = $this->product->all($where, [], 0, 1);

        if (empty($result)) {
            return [];
        }

        $model = current($result);
        $availability = $this->terminal === null
            ? []
            : $this->getAvailability((string)$model->code, (string)$this->terminal->codalmacen);
        return [
            'code' => $model->code,
            'description' => $model->description,
            'thumbnail' => $model->thumbnail ?? '',
            'bloqueado' => $model->bloqueado ?? false,
            'stock' => (float)($availability['stock'] ?? $model->stock ?? 0),
            'allow_no_stock' => (bool)($availability['allow_no_stock'] ?? $model->allow_no_stock ?? false),
        ];
    }

    public function getAvailability(string $code, string $warehouse = ''): array
    {
        if ($code === '') {
            return [];
        }

        $products = $this->product->all([Where::eq('V.referencia', $code)], [], 0, 1);
        if (empty($products)) {
            return [];
        }

        $product = current($products);
        $stock = (float)($product->stock ?? 0);
        if ($warehouse !== '') {
            $stock = array_reduce(
                $this->getStock($code, $warehouse),
                static fn(float $total, ProductoStock $row): float => $total + (float)$row->stock,
                0.0
            );
        }

        return [
            'stock' => $stock,
            'allow_no_stock' => (bool)($product->allow_no_stock ?? false),
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

    /**
     * Applies the terminal line-price rule to catalogue prices so the product
     * list and detail modal show the same tax-inclusive price as the cart.
     *
     * @param ProductoVariante[] $products
     */
    private function applyLinePriceRounding(array $products): void
    {
        if ($this->terminal === null) {
            return;
        }

        $mode = $this->terminal->getLineRoundingMode();
        $decimals = $this->terminal->getLineRoundingDecimals();

        foreach ($products as $product) {
            $product->priceWithTax = LinePriceRounder::roundGrossPrice(
                (float)$product->priceWithTax,
                $mode,
                $decimals
            );
            $product->priceWithFormat = Tools::number($product->priceWithTax);
        }
    }

    /**
     * Product source controls catalogue visibility, but sales always consume
     * stock from the terminal warehouse. Keep the displayed limit aligned
     * with the warehouse used by the generated document.
     *
     * @param ProductoVariante[] $products
     */
    private function applyTerminalAvailability(array $products): void
    {
        if ($this->terminal === null) {
            return;
        }

        $references = array_values(array_unique(array_filter(array_map(
            static fn(ProductoVariante $product): string => (string)$product->code,
            $products
        ))));
        if ($references === []) {
            return;
        }

        $stockByReference = array_fill_keys($references, 0.0);
        $stockRows = ProductoStock::all([
            Where::in('S.referencia', implode(',', $references)),
            Where::eq('S.codalmacen', (string)$this->terminal->codalmacen),
        ]);
        foreach ($stockRows as $stockRow) {
            $stockByReference[(string)$stockRow->code] = (float)$stockRow->stock;
        }

        foreach ($products as $product) {
            $product->stock = $stockByReference[(string)$product->code] ?? 0.0;
            $product->isOutOfStock = $product->stock <= 0 && !$product->allow_no_stock;
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

        $familyRates = TarifaFamilia::all($where);

        foreach ($familyRates as $familyRate) {
            $cacheKey = $familyRate->codfamilia . '-' . $familyRate->codtarifa;
            $this->familyRateCache[$cacheKey] = $familyRate;
        }
    }
}
