<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\DataSrc\FormasPago;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class FormaPagoPuntoVenta extends Base\ModelClass
{
    use Base\ModelTrait;

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

    public function descripcion()
    {
        return FormasPago::get($this->codpago)->descripcion;
    }
}
