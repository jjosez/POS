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
 * Sesion en la que se registran las operaciones de las terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class SesionPOS extends Base\ModelClass
{
    use Base\ModelTrait;

    public $abierto;
    public $conteo;
    public $fechainicio;
    public $fechafin;
    public $horainicio;
    public $horafin;
    public $idsesion;
    public $idterminal;
    public $nickusuario;
    public $saldocontado;    
    public $saldoesperado;
    public $saldoinicial; 
    public $saldomovimientos;
    public $saldoretirado;

    public function clear()
    {
        parent::clear();

        $this->abierto = false;
        $this->fechainicio = date('d-m-Y');
        $this->horainicio = date('H:i:s');
        // Pendiente hasta que se actualize la version del core.
        // $this->fechainicio = date(self::DATE_STYLE);
        // $this->horainicio = date(self::HOUR_STYLE);
        $this->saldocontado = 0.0;
        $this->saldoesperado = 0.0;
    }  

    public function install()
    {
        new TerminalPOS();
        return parent::install();
    }

    public function isOpen($search, $value)
    {
        switch ($search) {
            case 'terminal':
                $where = [
                  new DataBaseWhere('idterminal', $value, '='),
                  new DataBaseWhere('abierto', true, '=')
                ];
                break;

            case 'user':
                $where = [
                  new DataBaseWhere('nickusuario', $value, '='),
                  new DataBaseWhere('abierto', true, '=')
                ];
                break;
            
            default:
                # code...
                break;
        }

        return $this->loadFromCode('', $where);
    }

    public static function primaryColumn()
    {
        return 'idsesion';
    }

    public static function tableName()
    {
        return 'sesionespos';
    }
}
