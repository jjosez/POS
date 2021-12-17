<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

/**
 * Sesion en la que se registran las operaciones de las terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class SesionPuntoVenta extends Base\ModelClass
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
        new TerminalPuntoVenta();
        return parent::install();
    }

    public function isOpen($search, $value): bool
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
                // code...
                break;
        }

        return $this->loadFromCode('', $where);
    }

    /**
     * Returns the operations associated with the sessionpos.
     *
     * @return OrdenPuntoVenta[]
     */
    public function getOperaciones(): array
    {
        $operacion = new OrdenPuntoVenta();
        $where = [new DataBaseWhere('idsesion', $this->idsesion)];
        $order = ['idsesion' => 'ASC'];

        return $operacion->all($where, $order, 0, 0);
    }

    /**
     * @return array
     */
    public function getPagos(): array
    {
        $pago = new PagoPuntoVenta();
        $where = [new DataBaseWhere('idsesion', $this->idsesion)];

        return $pago->all($where, [], 0, 0);
    }

    /**
     * @return array
     */
    public function getPagosTotales(): array
    {
        $result = [];
        foreach ($this->getPagos() as $pago) {
            if (array_key_exists($pago->codpago, $result)) {
                $result[$pago->codpago]['total'] += $pago->pagoNeto();
            } else {
                $result[$pago->codpago]['total'] = $pago->pagoNeto();
                $result[$pago->codpago]['descripcion'] = $pago->descripcion();
            }
        }

        return $result;
    }

    public static function primaryColumn(): string
    {
        return 'idsesion';
    }

    public static function tableName(): string
    {
        return 'sesionespos';
    }
}
