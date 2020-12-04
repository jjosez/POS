<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\FormaPago;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PagoPOS extends Base\ModelClass
{
    use Base\ModelTrait;

    public $codpago;
    public $idoperacion;
    public $idpago;
    public $idsesion;
    public $cantidad;
    public $cambio;

    public function clear()
    {
        parent::clear();
        $this->cantidad = 0;
        $this->cambio = 0;
    }

    public function install()
    {
        new OperacionPOS();
        new SesionPOS();
        return parent::install();
    }

    public static function primaryColumn()
    {
        return 'idpago';
    }

    public static function tableName()
    {
        return 'pagospos';
    }

    public function pagoNeto()
    {
        return $this->cantidad - $this->cambio;
    }

    public function descripcion()
    {
        return (new FormaPago())->get($this->codpago)->descripcion;
    }
}
