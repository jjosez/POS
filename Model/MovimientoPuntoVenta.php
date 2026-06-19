<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class MovimientoPuntoVenta extends ModelClass
{
    use ModelTrait;

    public $descripcion;
    public $id;
    public $idsesion;
    public $fecha;
    public $hora;
    public $nickusuario;
    public $total;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
        $this->hora = Tools::hour();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pos_cash_movements';
    }

    /**
     * Returns all orders from a given session ID.
     *
     * @param string $code
     *
     * @return MovimientoPuntoVenta[]
     */
    public function allFromSession(string $code): array
    {
        return $this->all([
            Where::eq('idsesion', $code)
        ]);
    }
}
