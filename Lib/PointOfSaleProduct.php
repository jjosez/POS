<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Tools;
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

    public static function getThumbnailUrl2(?string $id, ?string $code): string
    {
        if (true === empty($id)) {
            return '';
        }

        $productImage = new ProductoImagen();

        if ($productImage->loadFromCode('', [
            new DataBaseWhere('idproducto', $id),
            new DataBaseWhere('referencia', null, 'IS', 'AND'),
            new DataBaseWhere('referencia', $code, '=', 'OR')
        ])) {
            return FS_ROUTE . $productImage->getThumbnail(150, 150, true);
        }

        return '';
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
        $products = self::getProduct()->all($where, [], 0, 30);

        foreach ($products as &$product) {
            $product->priceWithTax = $product->price * (100 + $product->getTax()->iva) / 100;
            $product->priceWithFormat = Tools::money($product->priceWithTax);
            $product->thumbnail = ProductoVariante::getThumbnailUrl($product->id, $product->code);
        }

        return $products;
    }

    /**
     * @param string $text
     * @param array $tags
     * @param string $wharehouse
     * @param string $company
     * @return array
     */
    public static function search2(string $text, array $tags = [], string $wharehouse = '', string $company = ''): array
    {
        //$time_start = floor(microtime(true) * 1000);

        $query = self::queryFields() . self::queryFrom() . self::queryWhere($text);

        $database = new DataBase();
        $products = $database->selectLimit($query);

        foreach ($products as &$product) {
            $priceWithTax = self::getPriceWithTaxt($product['price'], $product['codimpuesto']);

            $product['priceWithTax'] = $priceWithTax;
            $product['priceWithFormat'] = Tools::money($priceWithTax);
            $product['thumbnail'] = self::getThumbnailURL($product['imageid'], $product['imagefileid']);
        }

        //$time_end = floor(microtime(true) * 1000);
        //$time_lapse = $time_end - $time_start;

        //Tools::log('POS')->warning('Query Raw Time response: ' . $time_lapse);
        //Tools::log('POS')->warning('Query Raw: ' . $query);

        return $products;
    }

    protected static function getPriceWithTaxt(float $price = 0.0, string $taxcode = ''): float
    {
        return $price * (100 + Impuestos::get($taxcode)->iva) / 100;
    }

    protected static function getThumbnailURL(?string $id, ?string $idfile): string
    {
        if (true === empty($id)) {
            return '';
        }

        $productImage = new ProductoImagen([
            'id' => $id,
            'idfile' => $idfile
        ]);

        //$productImage = (new ProductoImagen())->get($id);
        return $productImage ? (FS_ROUTE . $productImage->getThumbnail(150, 150, true)) : '';
    }

    /**
     * @return CodeModel|false
     */
    public static function searchBarcode(string $text)
    {
        $result = self::getVariante()->codeModelSearch($text, 'referencia');

        return empty($result) ? false : current($result);
    }

    protected static function queryFields(): string
    {
        return 'SELECT P.idproducto id,'
            . ' V.referencia code,'
            . ' P.codimpuesto codimpuesto,'
            . ' V.codbarras barcode,'
            . ' P.descripcion description,'
            . ' V.precio price,'
            . ' SUM(S.disponible) stock,'
            . ' CONCAT_WS(\' - \', A1.descripcion, A2.descripcion, A3.descripcion, A4.descripcion) detail,'
            . ' A1.descripcion atribute1,'
            . ' A2.descripcion atribute2,'
            . ' A3.descripcion atribute3,'
            . ' A4.descripcion atribute4,'
            . ' I.id imageid, I.idfile imagefileid ';
    }

    protected static function queryFrom(): string
    {
        return ' FROM variantes V LEFT JOIN productos P ON V.idproducto = P.idproducto'
            . ' LEFT JOIN atributos_valores A1 ON V.idatributovalor1 = A1.id'
            . ' LEFT JOIN atributos_valores A2 ON V.idatributovalor2 = A2.id'
            . ' LEFT JOIN atributos_valores A3 ON V.idatributovalor3 = A3.id'
            . ' LEFT JOIN atributos_valores A4 ON V.idatributovalor4 = A4.id'
            . ' LEFT JOIN stocks S ON V.referencia = S.referencia'
            . ' LEFT JOIN productos_imagenes I ON V.idproducto = I.idproducto'
            . ' AND (I.referencia IS NULL OR V.referencia = I.referencia)';
    }

    protected static function queryWhere(string $text = ''): string
    {
        $text = Tools::noHtml($text);

        return " WHERE (LOWER(V.codbarras) LIKE LOWER('%" . $text . "%')"
            . " OR LOWER(V.referencia) LIKE LOWER('%" . $text . "%')"
            . " OR (LOWER(P.descripcion) LIKE LOWER('%" . $text . "%')))"
            . " GROUP BY V.referencia, I.id";
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
