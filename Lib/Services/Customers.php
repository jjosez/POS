<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Customer service for POS operations.
 * Handles customer search and creation.
 */
class Customers
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
     * @param string $taxID
     * @param string $name
     * @return bool
     */
    public function saveNew(string $taxID, string $name): bool
    {
        $this->customer->cifnif = $taxID;
        $this->customer->nombre = $name;
        $this->customer->razonsocial = $name;

        return $this->customer->save();
    }

    /**
     * @param string $text
     * @return array
     */
    public function search(string $text): array
    {
        return $this->getCustomer()->codeModelSearch($text);
    }
}
