<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
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
