<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Una terminal POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class TerminalPuntoVenta extends Base\ModelClass
{
    use Base\ModelTrait;
    use Base\CompanyRelationTrait;

    public const PRODUCTS_FROM_COMPANY = 1;
    public const PRODUCTS_FROM_WAREHOUSE = 2;

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

    public $idformatoticket;

    public $idterminal;
    public $nombre;
    public $numerotickets;
    public $productsource;
    public $restringealmacen;

    public $productolibre;

    public function clear()
    {
        parent::clear();

        $this->aceptapagos = true;
        $this->anchopapel = 45;
        $this->restringealmacen = false;
        $this->defaultdocument = 'FacturaCliente';
        $this->disponible = true;
        $this->numerotickets = 1;
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
            new DataBaseWhere('disponible', true, '=')
        ];

        if ($idempresa) {
            $where[] = new DataBaseWhere('idempresa', $idempresa);
        }

        return $this->all($where);
    }

    /**
     * @return Cliente
     */
    public function getDefaultCustomer(): Cliente
    {
        $customer = new Cliente();
        $customer->loadFromCode($this->codcliente);

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
        return FormaPagoPuntoVenta::all([new DataBaseWhere('idterminal', $this->idterminal)]);
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
        return TipoDocumentoPuntoVenta::all([new DataBaseWhere('idterminal', $this->idterminal)]);
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
