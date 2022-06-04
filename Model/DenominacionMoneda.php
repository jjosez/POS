<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;

/**
 * Cash denomination .
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class DenominacionMoneda extends Base\ModelClass
{
    use Base\ModelTrait;

    public $clave;
    public $coddivisa;
    public $valor;

    public static function primaryColumn(): string
    {
        return 'clave';
    }

    public static function tableName(): string
    {
        return 'denominacionesmoneda';
    }
}
