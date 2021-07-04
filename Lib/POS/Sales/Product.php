<?php


namespace FacturaScripts\Plugins\POS\Lib\POS\Sales;

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

    /**
     * @return Variante
     */
    public function getVariante(): Variante
    {
        return $this->variante;
    }

    /**
     * @param string $text
     * @return false|string
     */
    public function searchByText(string $text): string
    {
        $text = str_replace(" ", "%", $text);
        echo empty($text);

        return json_encode($this->queryProduct($text));
    }

    /**
     * @param string $text
     * @return false|string
     */
    public function searchBarcode(string $text): string
    {
        $queryResult = $this->queryProduct($text);
        $result = empty($queryResult) ? false : $queryResult[0];

        return json_encode($result);
    }

    /**
     * @param string $text
     * @return CodeModel[]|array
     */
    protected function queryProduct(string $text): array
    {
        return $this->getVariante()->codeModelSearch($text, 'referencia');
    }
}
