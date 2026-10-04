<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

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
    public $icon;
    public $idterminal;
    public $recibecambio;

    private const ALLOWED_ICONS = [
        'fa-credit-card',
        'fa-money-bill-1',
        'fa-money-check-dollar',
        'fa-tag',
        'fa-user-circle',
    ];

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pos_payment_methods';
    }

    public function descripcion(): string
    {
        return FormasPago::get($this->codpago)->descripcion;
    }

    public function formaPago(): FormaPago
    {
        return FormasPago::get($this->codpago);
    }

    public function iconClass(): string
    {
        if (in_array($this->icon, self::ALLOWED_ICONS, true)) {
            return $this->icon;
        }

        return $this->recibecambio ? 'fa-money-bill-1' : 'fa-credit-card';
    }
}
