<?php
/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\EasyPOS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

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
}
