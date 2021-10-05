<?php


namespace FacturaScripts\Plugins\POS\Lib\POS\Sales;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Variante;

class Product
{
    /**
     * @var Variante
     */
    protected $variante;

    public function __construct()
    {
        $this->variante = new Variante();
    }

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
    public function searchRequest(string $text): array
    {
        return $this->queryProduct($text);
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
     * @return CodeModel|false
     */
    public function searchBarcode(string $text)
    {
        $queryResult = $this->queryProduct($text);

        return empty($queryResult) ? false : $queryResult[0];
    }

    /**
     * @param string $text
     * @return CodeModel[]|array
     */
    protected function queryProduct(string $text): array
    {
        return empty($text) ? [] : $this->getVariante()->codeModelSearch($text, 'referencia');
    }
}
