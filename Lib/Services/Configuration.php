<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\DenominacionMoneda;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Plugins\POS\Model\TipoDocumentoPuntoVenta;

/**
 * POS configuration service.
 * Manages terminal settings, payment methods, and defaults.
 */
class Configuration
{
    private TerminalPuntoVenta $terminal;

    public function __construct(TerminalPuntoVenta $terminal)
    {
        $this->terminal = $terminal;
    }

    public function getCashPaymentMethod(): string
    {
        return $this->terminal->getCashPaymentMethod();
    }

    public function getDefaultCustomer(): Cliente
    {
        $customer = new Cliente();
        $customer->load($this->terminal->codcliente);
        return $customer;
    }

    public function getDefaultDocument(): TipoDocumentoPuntoVenta
    {
        return $this->terminal->getDefaultDocument();
    }

    public function getSupportedDocuments(): array
    {
        return $this->terminal->getSupportedDocuments();
    }

    public function getDenominations(): array
    {
        return DenominacionMoneda::all([], ['valor' => 'ASC']);
    }

    public function getPaymentMethods(): array
    {
        return $this->terminal->getSupportedPaymentMethods();
    }

    public function getDefaultWarehouse(): string
    {
        return $this->terminal->codalmacen ?: '';
    }

    public function getTerminal(): TerminalPuntoVenta
    {
        return $this->terminal;
    }
}
