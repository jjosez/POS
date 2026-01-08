<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core;

use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Plugins\POS\Lib\Services\Agents;
use FacturaScripts\Plugins\POS\Lib\Services\Configuration;
use FacturaScripts\Plugins\POS\Lib\Services\Currencies;
use FacturaScripts\Plugins\POS\Lib\Services\Customers;
use FacturaScripts\Plugins\POS\Lib\Services\Families;
use FacturaScripts\Plugins\POS\Lib\Services\Payments;
use FacturaScripts\Plugins\POS\Lib\Services\Products;
use FacturaScripts\Plugins\POS\Lib\Services\SessionStorage;
use FacturaScripts\Plugins\POS\Lib\Services\Transactions;

/**
 * Service container with lazy loading.
 * Provides unified access to all POS services with optimal performance.
 */
class Context
{
    private array $services = [];
    private SesionPuntoVenta $session;
    private TerminalPuntoVenta $terminal;

    public function __construct(SesionPuntoVenta $session, TerminalPuntoVenta $terminal)
    {
        $this->session = $session;
        $this->terminal = $terminal;
    }

    public function agents(): Agents
    {
        return $this->services['agents'] ??= new Agents();
    }

    /**
     * Session storage service (drafts, orders, cash movements).
     * Lazy loaded - only instantiated when first accessed.
     */
    public function storage(): SessionStorage
    {
        return $this->services['storage'] ??= new SessionStorage($this->session);
    }

    /**
     * Configuration service (terminal settings, payment methods, etc.).
     * Lazy loaded - only instantiated when first accessed.
     */
    public function config(): Configuration
    {
        return $this->services['config'] ??= new Configuration($this->terminal);
    }

    public function currency(): Currencies
    {
        return $this->services['currency'] ??= new Currencies();
    }

    /**
     * Product service (search, stock, variants, etc.).
     * Lazy loaded - only instantiated when first accessed.
     */
    public function products(): Products
    {
        return $this->services['products'] ??= new Products();
    }

    /**
     * Families service.
     * Lazy loaded - only instantiated when first accessed.
     */
    public function families(): Families
    {
        return $this->services['families'] ??= new Families();
    }

    /**
     * Customer service (search, create, etc.).
     * Lazy loaded - only instantiated when first accessed.
     */
    public function customers(): Customers
    {
        return $this->services['customers'] ??= new Customers();
    }

    /**
     * Transaction service (document creation, payments, etc.).
     * Lazy loaded - only instantiated when first accessed.
     */
    public function transactions(): Transactions
    {
        return $this->services['transactions'] ??= new Transactions();
    }

    /**
     * Payment service (receipts, cash management, etc.).
     * Lazy loaded - only instantiated when first accessed.
     */
    public function payments(): Payments
    {
        return $this->services['payments'] ??= new Payments($this->session);
    }

    /**
     * Returns the current session.
     * Direct access - no lazy loading needed.
     */
    public function session(): SesionPuntoVenta
    {
        return $this->session;
    }

    /**
     * Returns the current terminal.
     * Direct access - no lazy loading needed.
     */
    public function terminal(): TerminalPuntoVenta
    {
        return $this->terminal;
    }

    /**
     * Clears all cached services.
     * Useful for testing or when session changes.
     */
    public function clear(): void
    {
        $this->services = [];
    }
}
