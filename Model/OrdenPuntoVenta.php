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
class OrdenPuntoVenta extends Base\ModelClass
{
    use Base\ModelTrait;

    public $codcliente;
    public $fecha;
    public $hora; 
    public $iddocumento;   
    public $idoperacion;
    public $idsesion;
    public $tipodoc; 
    public $total;

    public function clear()
    {
        parent::clear();
        $this->fecha = date(self::DATE_STYLE);
        $this->hora = date(self::HOUR_STYLE);
    }

    public static function primaryColumn(): string
    {
        return 'idoperacion';
    }

    public static function tableName(): string
    {
        return 'operacionespos';
    }

    /**
     * Returns all orders from given session ID.
     *
     * @param string $code
     *
     * @return OrdenPuntoVenta[]
     */
    public function allFromSession(string $code)
    {
        $where = [new DataBaseWhere('idsesion', $code)];

        return $this->all($where);
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List'): string
    {
        $value = $this->iddocumento;
        $model = $this->tipodoc;
        switch ($type) {
            case 'edit':
                return is_null($value) ? 'Edit' . $model : 'Edit' . $model . '?code=' . $value;

            case 'list':
                return $list . $model;

            case 'new':
                return 'Edit' . $model;
        }

        /// default
        return empty($value) ? $list . $model : 'Edit' . $model . '?code=' . $value;
    }
}
