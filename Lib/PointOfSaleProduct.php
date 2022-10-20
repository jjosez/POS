<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\ProductoImagen;
use FacturaScripts\Dinamic\Model\Variante;
use FacturaScripts\Dinamic\Model\Join\ProductoStock;
use FacturaScripts\Dinamic\Model\Join\ProductoVariante;

class PointOfSaleProduct
{
    /**
     * @var ProductoVariante
     */
    protected $product;

    /**
     * @var Variante
     */
    protected $variante;

    public function __construct()
    {
        $this->variante = new Variante();
        $this->product = new ProductoVariante();
    }

    /**
     * @param string $idempresa
     * @return DataBaseWhere
     */
    protected function getCompanyDatabaseWhere(string $idempresa): DataBaseWhere
    {
        $almacenes = [];
        foreach (Almacenes::all() as $almacen) if ((string)$almacen->idempresa === $idempresa) {
            $almacenes[] = $almacen->codalmacen;
        }

        return new DataBaseWhere('S.codalmacen', implode(',', $almacenes), 'IN');
    }

    /**
     * @return ProductoImagen[]
     */
    public function getImages(string $id, string $code): array
    {
        $where = [
            new DataBaseWhere('idproducto', $id),
            new DataBaseWhere('referencia', null, 'IS', 'AND'),
            new DataBaseWhere('referencia', $code, '=', 'OR')
        ];

        return (new ProductoImagen())->all($where);
    }

    /**
     * @return String[]
     */
    public function getImagesURL(string $id, string $code): array
    {
        $routes = [];

        foreach ($this->getImages($id, $code) as $image) {
            $routes[] = FS_ROUTE . '/' . $image->url('download-permanent');
        }

        return $routes;
    }

    /**
     * @return ProductoVariante
     */
    public function getProductoVariante(): ProductoVariante
    {
        return $this->product;
    }

    /**
     * @param string $code
     * @return ProductoStock[]
     */
    public function getStock(string $code): array
    {
        $where = [
            new DataBaseWhere('LOWER(S.referencia)', mb_strtolower($code, 'UTF8'))
        ];

        $stock = new ProductoStock();

        return $stock->all($where);
    }

    /**
     * @return Variante
     */
    public function getVariante(): Variante
    {
        return $this->variante;
    }

    /**
     * @param string $text
     * @param array $tags
     * @param string $wharehouse
     * @param string $company
     * @return array
     */
    public function search(string $text, array $tags = [], string $wharehouse = '', string $company = ''): array
    {
        $where = [
            new DataBaseWhere('V.codbarras', $text, 'LIKE'),
            new DataBaseWhere('V.referencia', $text, 'LIKE', 'OR'),
            new DataBaseWhere('P.descripcion', $text, 'XLIKE', 'OR')
        ];

        if ($company) {
            $where[] = $this->getCompanyDatabaseWhere($company);
        } elseif ($wharehouse) {
            $where[] = new DataBaseWhere('S.codalmacen', $wharehouse);
            $where[] = new DataBaseWhere('S.codalmacen', NULL, 'IS', 'OR');
        }

        /*foreach ($tags as $tag) {
            $where[] = new DataBaseWhere('codfamilia', $tag, '=', 'AND');
        }*/
        $products = $this->getProductoVariante()->all($where, [], 0, FS_ITEM_LIMIT);

        foreach ($products as $product) {
            $images = $this->getImagesURL($product->id, $product->code);
            $product->image = $images ? $images[0] : '';
        }

        return $products;
    }

    /**
     * @param string $text
     * @return false|CodeModel
     */
    public function searchBarcode(string $text)
    {
        $result = $this->getVariante()->codeModelSearch($text, 'referencia');

        return empty($result) ? false : current($result);
    }
}
