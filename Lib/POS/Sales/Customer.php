<?php


namespace FacturaScripts\Plugins\POS\Lib\POS\Sales;


use FacturaScripts\Dinamic\Model\Cliente;

class Customer
{
    /**
     * @var Cliente
     */
    protected $customer;

    public function __construct()
    {
        $this->customer = new Cliente();
    }

    /**
     * @return Cliente
     */
    public function getCustomer(): Cliente
    {
        return $this->customer;
    }

    public function saveNew(string $taxID, string $name): bool
    {
        $this->customer->cifnif = $taxID;
        $this->customer->nombre = $name;
        $this->customer->razonsocial = $name;

        return $this->customer->save();
    }

    /**
     * @param string $text
     * @return false|string
     */
    public function search(string $text): string
    {
        $result = empty($text) ? [] : $this->getCustomer()->codeModelSearch($text);

        return json_encode($result);
    }
}
