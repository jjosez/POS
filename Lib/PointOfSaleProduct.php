<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Join\ProductoStock;
use FacturaScripts\Dinamic\Model\Join\ProductoVariante;
use FacturaScripts\Dinamic\Model\Variante;

class PointOfSaleProduct
{
    /**
     * @var ProductoVariante
     */
    private static $product;

    /**
     * @var Variante
     */
    private static $variante;

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
     * @return String[]
     */
    public static function getImagesUrl(string $id, string $code): array
    {
        $routes = [];

        foreach (ProductoVariante::getImages($id, $code) as $image) {
            $routes[] = FS_ROUTE . '/' . $image->url('download-permanent');
        }

        return $routes;
    }

    /**
     * @param string $code
     * @return ProductoStock[]
     */
    public static function getStock(string $code): array
    {
        $where = [
            new DataBaseWhere('LOWER(S.referencia)', mb_strtolower($code, 'UTF8'))
        ];

        return (new ProductoStock())->all($where);
    }

    /**
     * @param string $text
     * @param array $filters
     * @param string $wharehouse
     * @param string $company
     * @return array
     */
    public static function search(string $text, array $filters = [], string $wharehouse = '', string $company = ''): array
    {
        $where = [
            new DataBaseWhere('V.codbarras', $text, 'LIKE'),
            new DataBaseWhere('V.referencia', $text, 'LIKE', 'OR'),
            new DataBaseWhere('P.descripcion', $text, 'XLIKE', 'OR'),
            new DataBaseWhere('P.sevende', true)
        ];

        if ($company) {
            $where[] = self::getCompanyDatabaseWhere($company);
        } elseif ($wharehouse) {
            $where[] = new DataBaseWhere('S.codalmacen', $wharehouse);
            $where[] = new DataBaseWhere('S.codalmacen', NULL, 'IS', 'OR');
        }

        if (!empty($filters['families'])) {
            $families = implode(',', array_column($filters['families'], 'code'));

            $where[] = new DataBaseWhere('codfamilia', $families, 'IN');
        }

        //return PointOfSaleProductVariant::search($text, $filters);

        return self::getProduct()->all($where, [], 0, 30);
    }

    /**
     * @return CodeModel|false
     */
    public static function searchBarcode(string $text): bool|CodeModel
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
        if (!isset(self::$product)) {
            self::$product = new ProductoVariante();
        }

        return self::$product;
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

    protected static function searchProduct($searchTerm): array
    {
        $dataBase = new DataBase();

        $query = "SELECT P.idproducto id, " .
            "V.referencia code, " .
            "P.codimpuesto codimpuesto, " .
            "V.codbarras barcode, " .
            "P.descripcion description, " .
            "V.precio price, " .
            "SUM(S.disponible) stock, " .
            "CONCAT_WS(' - ', A1.descripcion, A2.descripcion, A3.descripcion, A4.descripcion) detail, " .
            "A1.descripcion atribute1, " .
            "A2.descripcion atribute2, " .
            "A3.descripcion atribute3, " .
            "A4.descripcion atribute4 " .
            "FROM variantes V " .
            "LEFT JOIN productos P ON V.idproducto = P.idproducto " .
            "LEFT JOIN atributos_valores A1 ON V.idatributovalor1 = A1.id " .
            "LEFT JOIN atributos_valores A2 ON V.idatributovalor2 = A2.id " .
            "LEFT JOIN atributos_valores A3 ON V.idatributovalor3 = A3.id " .
            "LEFT JOIN atributos_valores A4 ON V.idatributovalor4 = A4.id " .
            "LEFT JOIN stocks S ON V.referencia = S.referencia " .
            "WHERE (LOWER(V.codbarras) LIKE LOWER('%" . $searchTerm . "%') " .
            "OR LOWER(V.referencia) LIKE LOWER('%" . $searchTerm . "%') " .
            "OR (LOWER(P.descripcion) LIKE LOWER('%" . $searchTerm . "%'))) " .
            "GROUP BY P.idproducto, V.referencia, P.codimpuesto, V.codbarras, " .
            "P.descripcion, V.precio, A1.descripcion, A2.descripcion, A3.descripcion, A4.descripcion";

        return $dataBase->selectLimit($query, 50);
    }
}
