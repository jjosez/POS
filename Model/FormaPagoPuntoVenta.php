<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Dinamic\Model\FormaPago;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class FormaPagoPuntoVenta extends ModelClass
{
    use ModelTrait;

    public $codpago;
    public $cantidad;
    public $idterminal;
    public $recibecambio;

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'formaspagopos';
    }

    public function descripcion(): string
    {
        return FormasPago::get($this->codpago)->descripcion;
    }

    public function formaPago(): FormaPago
    {
        return FormasPago::get($this->codpago);
    }
}
