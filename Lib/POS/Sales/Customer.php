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

    /**
     * @param string $text
     * @return false|string
     */
    public function searchCustomer(string $text): string
    {
        $result = $this->getCustomer()->codeModelSearch($text);

        return json_encode($result);
    }
}
