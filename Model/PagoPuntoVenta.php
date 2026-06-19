<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PagoPuntoVenta extends ModelClass
{
    use ModelTrait;

    public $isCashMethod;

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


    public function clear(): void
    {
        parent::clear();
        $this->cantidad = 0;
        $this->cambio = 0;

        $this->createdat = Tools::dateTime();
        $this->updatedat = null;

        $this->nick = Session::user()->nick;
        $this->nickupdate = null;
        $this->isCashMethod = false;
    }

    public function install(): string
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
        return 'pos_payments';
    }

    public function pagoNeto(): float
    {
        return $this->cantidad - $this->cambio;
    }

    public function descripcion(): string
    {
        return FormasPago::get($this->codpago)->descripcion;
    }

    protected function saveUpdate(array $values = []): bool
    {
        $this->nickupdate = Session::user()->nick;
        $this->updatedat = Tools::dateTime();

        return parent::saveUpdate();
    }

    public function getOrdenPuntoVenta(): OrdenPuntoVenta
    {
        $order = new OrdenPuntoVenta();
        $order->load($this->idoperacion);

        return $order;
    }

    public function getSesionPuntoVenta(): SesionPuntoVenta
    {
        $sesion = new SesionPuntoVenta();
        $sesion->load($this->idsesion);

        return $sesion;
    }

    public function test(): bool
    {
        $this->nickupdate = null;


        if (empty($this->idsesion)) {
            $orden = new OrdenPuntoVenta();

            if ($orden->load($this->idoperacion)) {
                $this->idsesion = $orden->idsesion;
            }
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        if (($type === 'list')) {
            return 'EditSesionPuntoVenta?code=' . $this->idsesion . '&activetab=ListPagoPuntoVenta';
        }

        return parent::url($type, $list);
    }
}
