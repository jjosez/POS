<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Model\Base\CompanyRelationTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Una terminal POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class TerminalPuntoVenta extends ModelClass
{
    use ModelTrait;
    use CompanyRelationTrait;

    public const PRODUCTS_FROM_COMPANY = 1;
    public const PRODUCTS_FROM_WAREHOUSE = 2;
    public const MODE_CASHIER = 'cashier';
    public const MODE_SELLER = 'seller';
    public const MODE_CUSTOMER = 'customer';

    public $anchopapel;
    public $aceptapagos;
    public $codalmacen;
    public $codcliente;
    public $codserie;
    public $comandoapertura;
    public $comandocorte;
    public $defaultdocument;
    public $disponible;
    public $idempresa;

    public $idterminal;
    public $nombre;
    public $numerotickets;
    public $productsource;
    public $restringealmacen;

    public $productolibre;

    public $terminal_type;

    public function clear(): void
    {
        parent::clear();

        $this->aceptapagos = true;
        $this->anchopapel = 45;
        $this->restringealmacen = false;
        $this->defaultdocument = 'FacturaCliente';
        $this->disponible = true;
        $this->numerotickets = 1;
        $this->terminal_type = self::MODE_CASHIER;
    }

    public static function primaryColumn(): string
    {
        return 'idterminal';
    }

    public static function tableName(): string
    {
        return 'terminalespos';
    }

    /**
     * @return TerminalPuntoVenta[]
     */
    public function getAvailable($idempresa = null): array
    {
        $where = [
            Where::eq('disponible', true),
        ];

        if ($idempresa) {
            $where[] = Where::eq('idempresa', $idempresa);
        }

        return self::all($where);
    }

    /**
     * @return Cliente
     */
    public function getDefaultCustomer(): Cliente
    {
        $customer = new Cliente();
        $customer->load($this->codcliente);

        return $customer;
    }

    /**
     * @return TipoDocumentoPuntoVenta
     */
    public function getDefaultDocument(): TipoDocumentoPuntoVenta
    {
        foreach (self::getSupportedDocuments() as $element) if ($element->preferido) {
            return $element;
        }

        return new TipoDocumentoPuntoVenta();
    }

    /**
     * @return FormaPagoPuntoVenta[]
     */
    public function getSupportedPaymenthMethods(): array
    {
        return FormaPagoPuntoVenta::all([
            Where::eq('idterminal', $this->idterminal)
        ]);
    }

    public function getCashPaymentMethod(): string
    {
        foreach ($this->getSupportedPaymenthMethods() as $element) if ($element->recibecambio) {
            return $element->codpago;
        }

        return '';
    }

    /**
     * @return TipoDocumentoPuntoVenta[]
     */
    public function getSupportedDocuments(): array
    {
        return TipoDocumentoPuntoVenta::all([
            new DataBaseWhere('idterminal', $this->idterminal)
        ]);
    }

    public function getWarehouse(): Almacen
    {
        return Almacenes::get($this->codalmacen);
    }

    public function save(): bool
    {
        $this->idempresa = $this->getWarehouse()->idempresa;

        return parent::save();
    }
}
