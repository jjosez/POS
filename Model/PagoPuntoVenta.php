<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\FormaPago;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PagoPuntoVenta extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * @var float
     */
    public $cantidad;

    /**
     * @var float
     */
    public $cambio;

    /**
     * @var string
     */
    public $codpago;

    /**
     * @var int
     */
    public $idoperacion;

    /**
     * @var int
     */
    public $idpago;


    /**
     * @var int
     */
    public $idsesion;

    /**
     * @var string
     */
    public $nick;

    /**
     * @var string
     */
    public $nickupdate;

    /**
     * @var string
     */
    public $createdat;

    /**
     * @var string
     */
    public $updatedat;


    public function clear()
    {
        parent::clear();
        $this->cantidad = 0;
        $this->cambio = 0;

        $this->createdat = Tools::dateTime();
        $this->updatedat = null;

        $this->nick = Session::user()->nick;
        $this->nickupdate = null;
    }

    public function install()
    {
        new SesionPuntoVenta();
        new OrdenPuntoVenta();
        new MovimientoPuntoVenta();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idpago';
    }

    public static function tableName(): string
    {
        return 'pagospos';
    }

    public function pagoNeto()
    {
        return $this->cantidad - $this->cambio;
    }

    public function descripcion(): string
    {
        return (new FormaPago())->get($this->codpago)->descripcion;
    }

    /*public function saveInsert(array $values = []): bool
    {
        if (false === parent::saveInsert($values)) {
            return false;
        }

        $orden = $this->getOrdenPuntoVenta();

        $paymentTracking = new PagoPuntoVentaSeguimiento();
        $paymentTracking->cantidad = $this->pagoNeto();
        $paymentTracking->modelfrom = $orden->tipodoc;
        $paymentTracking->idmodelfrom = $orden->iddocumento;
        $paymentTracking->idpagopos = $this->idpago;
        $paymentTracking->idmodelto = $orden->iddocumento;
        $paymentTracking->modelto = $this->idpago;

        return $paymentTracking->save();
    }*/

    protected function saveUpdate(array $values = []): bool
    {
        $this->nickmodifico = Session::user()->nick;
        $this->updatedat = Tools::dateTime();

        return parent::saveUpdate($values);
    }

    public function getOrdenPuntoVenta(): OrdenPuntoVenta
    {
        $order = new OrdenPuntoVenta();
        $order->loadFromCode($this->idoperacion);

        return $order;
    }

    public function test()
    {
        $sesionPOS = new OrdenPuntoVenta();

        if ($sesionPOS->loadFromCode($this->idoperacion) && empty($this->idsesion)) {
            $this->idsesion = $sesionPOS->idsesion;
        }

        return parent::test();
    }
}
