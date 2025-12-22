<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Join\ProductoStock;
use FacturaScripts\Dinamic\Model\Join\ProductoVariante;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Product service for POS operations.
 * Handles product search, stock management, and pricing.
 *
 * -- Índice en codbarras (búsqueda por barcode)
 * CREATE INDEX idx_variantes_codbarras ON variantes(codbarras);
 *
 * -- Índice en referencia
 * CREATE INDEX idx_variantes_referencia ON variantes(referencia);
 *
 * -- Índice fulltext para descripción (búsqueda de texto)
 * CREATE FULLTEXT INDEX idx_productos_descripcion ON productos(descripcion);
 */
class Products
{
    private ProductoVariante $product;
    private Variante $variante;

    public function __construct()
    {
        $this->product = new ProductoVariante();
        $this->variante = new Variante();
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
     * Searches products by text, filters, warehouse or company.
     *
     * @param string $text Search text (barcode, reference, or description)
     * @param array $filters Additional filters (e.g., families)
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

        return $this->product->all($where, [], 0, 30);
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
}
