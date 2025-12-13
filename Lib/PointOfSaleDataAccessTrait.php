<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\DenominacionMoneda;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Plugins\POS\Model\TipoDocumentoPuntoVenta;

/**
 * Trait providing data access methods for Point of Sale operations.
 */
trait PointOfSaleDataAccessTrait
{
    protected PointOfSaleSession $session;

    /**
     * Returns parent families configured as POS shortcuts.
     */
    public function getParentFamilies(): array
    {
        $where = [
            new DataBaseWhere('pos_shortcut', true)
        ];

        return Familia::all($where);
    }

    /**
     * Returns the cash payment method ID.
     */
    public function getCashPaymentMethod(): string
    {
        return $this->getTerminal()->getCashPaymentMethod();
    }

    /**
     * Returns custom buttons for the POS interface.
     */
    public function getCustomButtons(): array
    {
        return [];
    }

    /**
     * Returns the default customer for the terminal.
     */
    public function getDefaultCustomer(): Cliente
    {
        $customer = new Cliente();
        $customer->load($this->getTerminal()->codcliente);

        return $customer;
    }

    /**
     * Returns the default document type for the terminal.
     */
    public function getDefaultDocument(): TipoDocumentoPuntoVenta
    {
        return $this->getTerminal()->getDefaultDocument();
    }

    /**
     * Returns all supported document types for the terminal.
     */
    public function getSupportedDocuments(): array
    {
        return $this->getTerminal()->getSupportedDocuments();
    }

    /**
     * Returns all available currency denominations.
     */
    public function getDenominations(): array
    {
        return DenominacionMoneda::all([], ['valor' => 'ASC']);
    }

    /**
     * Returns fields available based on user permissions.
     */
    public function getFieldOptions(): array
    {
        return PointOfSaleForms::getFormsGrid($this->user->nick) ?? [];
    }

    /**
     * Returns the count of visible cart columns.
     */
    public function getCartColumnCount(): int
    {
        $count = 0;
        $excludedColumns = ['reference', 'description', 'quantity'];

        foreach ($this->getFieldOptions() as $column) {
            if (in_array($column['name'], $excludedColumns)) continue;

            if ($column['carrito']) $count++;
        }

        return $count;
    }

    /**
     * Returns some products for initial view.
     */
    public function getHomeProducts(): array
    {
        return PointOfSaleProduct::search('');
    }

    /**
     * Returns a random token to use as transaction id.
     */
    public function getNewToken(): string
    {
        return $this->multiRequestProtection->newToken();
    }

    /**
     * Returns all available payment methods for the terminal.
     */
    public function getPaymentMethods(): array
    {
        return $this->getTerminal()->getSupportedPaymenthMethods();
    }

    /**
     * Returns the default warehouse code.
     */
    public function getDefaultWarehouse(): string
    {
        return $this->getTerminal()->codalmacen ?: '';
    }

    /**
     * Returns the current user session.
     */
    public function getSession(): PointOfSaleSession
    {
        return $this->session;
    }

    /**
     * Returns the current user session terminal.
     */
    public function getTerminal(): TerminalPuntoVenta
    {
        return $this->session->getTerminal();
    }

    /**
     * Returns all terminals from the user's company.
     */
    public function getTerminalFromCompany(): array
    {
        return $this->session->getTerminal()->getAvailable($this->user->idempresa);
    }

    /**
     * Sets family filter and returns results.
     */
    public function setFamilyFilter(): void
    {
        $codfamilia = $this->request->request->get('code', '');

        $where = [new DataBaseWhere('madre', $codfamilia)];

        $familia = new Familia();
        $familia->load($codfamilia);

        $result = [
            'madre' => $familia->codfamilia ? $familia : '',
            'children' => $codfamilia ? $familia->all($where) : $this->getParentFamilies()
        ];

        $this->responseBuilder->setResponse($result);
    }
}
