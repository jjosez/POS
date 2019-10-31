<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class OperacionPOS extends Base\ModelClass
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

        $this->arqueo->fechafin = date('d-m-Y');
        $this->arqueo->horafin = date('H:i:s');
        // Pendiente hasta que se actualize la version del core.
        // $this->fecha = date(self::DATE_STYLE);
        // $this->hora = date(self::HOUR_STYLE);
    }  


    public function install()
    {
        new SesionPOS();
        return parent::install();
    }

    public static function primaryColumn()
    {
        return 'idoperacion';
    }

    public static function tableName()
    {
        return 'operacionespos';
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
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
