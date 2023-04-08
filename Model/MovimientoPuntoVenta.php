<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class MovimientoPuntoVenta extends Base\ModelClass
{
    use Base\ModelTrait;

    public $descripcion;
    public $id;
    public $idsesion;
    public $fecha;
    public $hora;
    public $nickusuario;
    public $total;

    public function clear()
    {
        parent::clear();
        $this->fecha = date(self::DATE_STYLE);
        $this->hora = date(self::HOUR_STYLE);
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'movimientospos';
    }

    /**
     * Returns all orders from given session ID.
     *
     * @param string $code
     *
     * @return MovimientoPuntoVenta[]
     */
    public function allFromSession(string $code)
    {
        $where = [new DataBaseWhere('idsesion', $code)];

        return $this->all($where);
    }
}
