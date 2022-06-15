<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Variante;
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
     * @return ProductoVariante
     */
    public function getProductoVariante(): ProductoVariante
    {
        return $this->product;
    }

    /**
     * @param string $code
     * @return float|int
     */
    public function getStock(string $code)
    {
        $producto = $this->getVariante();
        $producto->loadFromCode('', [new DataBaseWhere('referencia', $code)]);

        return $producto->stockfis ?? 0;
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
     * @return CodeModel[]
     */
    public function search(string $text): array
    {
        $text = str_replace(" ", "%", $text);

        return $this->queryProduct($text);
    }

    /**
     * @param string $text
     * @return false|CodeModel
     */
    public function searchBarcode(string $text)
    {
        $queryResult = $this->queryProduct($text);

        return empty($queryResult) ? false : $queryResult[0];
    }

    /**
     * @param string $text
     * @return array|CodeModel[]
     */
    protected function queryProduct(string $text): array
    {
        return $this->getVariante()->codeModelSearch($text, 'referencia');
    }

    /**
     * @param string $text
     * @param array $tags
     * @return array
     */
    public function advancedSearch(string $text, array $tags = [], string $wharehouse = ''): array
    {
        ///$text2 = str_replace(" ", "%", $text);
        $text = mb_strtolower($text, 'UTF8');

        $where = [
            new DataBaseWhere('LOWER(V.codbarras)', $text . '%', 'LIKE'),
            new DataBaseWhere('V.referencia', $text, 'LIKE', 'OR'),
            new DataBaseWhere('P.descripcion', $text, 'XLIKE', 'OR')
        ];

        if ($wharehouse && '' !== $wharehouse) {
            $where[] = new DataBaseWhere('S.codalmacen', 'ALG');
            $where[] = new DataBaseWhere('S.codalmacen', NULL, 'IS', 'OR');
        }

        foreach ($tags as $tag) {
            $where[] = new DataBaseWhere('codfamilia', $tag, '=', 'AND');
        }

        return $this->getProductoVariante()->all($where, [], 0, FS_ITEM_LIMIT);
    }
}
