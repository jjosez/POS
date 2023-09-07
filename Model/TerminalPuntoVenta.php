<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Dinamic\Model\Almacen;

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

    public function allAvailable($idempresa = false): array
    {
        $where = [
          new DataBaseWhere('disponible', true, '=')
        ];

        if ($idempresa) {
            $where[] = new DataBaseWhere('idempresa', $idempresa);
        }

        return $this->all($where);
    }

    public function getWarehouse(): Almacen
    {
        return Almacenes::get($this->codalmacen);
    }

    /**
     * @return FormaPagoPuntoVenta[]
     */
    public function getPaymenthMethods(): array
    {
        return (new FormaPagoPuntoVenta)->all([new DataBaseWhere('idterminal', $this->idterminal)]);
    }

    public function getCashPaymentMethod(): string
    {
        foreach ($this->getPaymenthMethods() as $element) if ($element->recibecambio) {
            return $element->codpago;
        }
        return '';
    }

    /**
     * @return TipoDocumentoPuntoVenta[]
     */
    public function getDocumentTypes(): array
    {
        return (new TipoDocumentoPuntoVenta())->all([new DataBaseWhere('idterminal', $this->idterminal)]);
    }

    public function save()
    {
        $this->idempresa = $this->getWarehouse()->idempresa;

        return parent::save();
    }
}
