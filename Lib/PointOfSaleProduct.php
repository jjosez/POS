<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Join\ProductoStock;
use FacturaScripts\Dinamic\Model\Join\ProductoVariante;
use FacturaScripts\Dinamic\Model\ProductoImagen;
use FacturaScripts\Dinamic\Model\Variante;

class PointOfSaleProduct
{
    /**
     * @var ProductoVariante
     */
    protected $product;

    private static $prodcuto;

    /**
     * @var Variante
     */
    private static $variante;

    public function __construct()
    {
        $this->product = new ProductoVariante();
    }

    /**
     * @param string $idempresa
     * @return DataBaseWhere
     */
    protected static function getCompanyDatabaseWhere(string $idempresa): DataBaseWhere
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
    public static function getImages(string $id, string $code): array
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
    public static function getImagesURL(string $id, string $code): array
    {
        $routes = [];

        foreach (self::getImages($id, $code) as $image) {
            $routes[] = FS_ROUTE . '/' . $image->url('download-permanent');
        }

        return $routes;
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
     * @param string $text
     * @param array $tags
     * @param string $wharehouse
     * @param string $company
     * @return array
     */
    public static function search(string $text, array $tags = [], string $wharehouse = '', string $company = ''): array
    {
        $where = [
            new DataBaseWhere('V.codbarras', $text, 'LIKE'),
            new DataBaseWhere('V.referencia', $text, 'LIKE', 'OR'),
            new DataBaseWhere('P.descripcion', $text, 'XLIKE', 'OR')
        ];

        if ($company) {
            $where[] = self::getCompanyDatabaseWhere($company);
        } elseif ($wharehouse) {
            $where[] = new DataBaseWhere('S.codalmacen', $wharehouse);
            $where[] = new DataBaseWhere('S.codalmacen', NULL, 'IS', 'OR');
        }

        /*foreach ($tags as $tag) {
            $where[] = new DataBaseWhere('codfamilia', $tag, '=', 'AND');
        }*/
        $products = self::getProduct()->all($where, [], 0, FS_ITEM_LIMIT);

        /*foreach ($products as $product) {
            $images = self::getImagesURL($product->id, $product->code);
            $product->image = $images ? $images[0] : '';
        }*/

        return $products;
    }

    /**
     * @return CodeModel|false
     */
    public static function searchBarcode(string $text)
    {
        $result = self::getVariante()->codeModelSearch($text, 'referencia');

        return empty($result) ? false : current($result);
    }

    /**
     *
     * @return ProductoVariante
     */
    protected static function getProduct(): ProductoVariante
    {
        if (!isset(self::$prodcuto)) {
            self::$prodcuto = new ProductoVariante();
        }

        return self::$prodcuto;
    }

    /**
     *
     * @return Variante
     */
    protected static function getVariante(): Variante
    {
        if (!isset(self::$variante)) {
            self::$variante = new Variante();
        }

        return self::$variante;
    }
}
