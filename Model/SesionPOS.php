<?php
/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\EasyPOS\Model;

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
        $this->fechainicio = date(self::DATE_STYLE);
        $this->horainicio = date(self::HOUR_STYLE);
        $this->saldocontado = 0.0;
        $this->saldoesperado = 0.0;
    }

    public function install()
    {
        new TerminalPOS();
        new PagoPOS();
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

    /**
     * Returns the operations associated with the sessionpos.
     *
     * @return OperacionPOS[]
     */
    public function getOperaciones()
    {
        $operacion = new OperacionPOS();
        $where = [new DataBaseWhere('idsesion', $this->idsesion)];
        $order = ['idsesion' => 'ASC'];

        return $operacion->all($where, $order, 0, 0);
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
