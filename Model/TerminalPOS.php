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
 * Una terminal POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class TerminalPOS extends Base\ModelClass
{
    use Base\ModelTrait;

    public $anchopapel;
    public $aceptapagos;
    public $codalmacen;
    public $codcliente;
    public $codserie;    
    public $comandoapertura;
    public $comandocorte;
    public $disponible;     
    public $idterminal;   
    public $nombre; 
    public $numerotickets;  

    public function clear()
    {
        parent::clear();
        
        $this->aceptapagos = true;
        $this->anchopapel = 45;
        $this->disponible = true;
        $this->numerotickets = 1;
    } 

    public static function primaryColumn()
    {
        return 'idterminal';
    }

    public static function tableName()
    {
        return 'terminalespos';
    }

    public function allAvailable()
    {
        $where = [
          new DataBaseWhere('disponible', true, '=')
        ];

        return $this->all($where);
    }
}
