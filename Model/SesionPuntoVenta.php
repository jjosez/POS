<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\OperacionPausada;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta as TerminalPuntoVenta;
use FacturaScripts\Dinamic\Model\User;

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
        $this->nickusuario = false;
        $this->saldocontado = 0.0;
        $this->saldoesperado = 0.0;
    }

    public function install()
    {
        new TerminalPuntoVenta();
        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idsesion';
    }

    public static function tableName(): string
    {
        return 'sesionespos';
    }

    /**
     * @param string|null $code
     * @return bool
     */
    public function completePausedOrder(?string $code): bool
    {
        $pausedOrder = new OperacionPausada();

        if ($code && $pausedOrder->loadFromCode($code)) {
            $pausedOrder->idestado = 3;

            return $pausedOrder->save();
        }
        return false;
    }

    /**
     * @param string $code
     * @return bool
     */
    public function deletePausedOrder(string $code): bool
    {
        $pausedOrder = new OperacionPausada();

        if ($code && $pausedOrder->loadFromCode($code)) {
            return $pausedOrder->delete();
        }

        return false;
    }

    /**
     * Returns the operations associated with the sessionpos.
     *
     * @return MovimientoPuntoVenta[]
     */
    public function getCashMovments(): array
    {
        $operacion = new MovimientoPuntoVenta();
        $where = [new DataBaseWhere('idsesion', $this->idsesion)];

        return $operacion->all($where);
    }

    /**
     * @param string $code
     * @return OrdenPuntoVenta|false
     */
    public function getOrder(string $code): OrdenPuntoVenta
    {
        $order = new OrdenPuntoVenta();

        return $order->get($code);
    }

    /**
     * @return OrdenPuntoVenta[]
     */
    public function getOrders(): array
    {
        $order = new OrdenPuntoVenta();
        $where = [new DataBaseWhere('idsesion', $this->idsesion)];

        return $order->all($where);
    }

    /**
     * @param string $code
     * @return OperacionPausada
     */
    public function getPausedOrder(string $code): OperacionPausada
    {
        $order = new OperacionPausada();
        $order->loadFromCode($code);

        $order->codigo = null;
        $order->fecha = date($order::DATE_STYLE);
        $order->hora = date($order::HOUR_STYLE);

        return $order;
    }

    /**
     * @return OrdenPuntoVenta[]
     */
    public function getPausedOrders($ownOrders = false): array
    {
        $pausedOrder = new OperacionPausada();

        $where = [new DataBaseWhere('editable', true)];

        if (true === $ownOrders) {
            $where[] = new DataBaseWhere('idsesion', $this->idsesion);
        }

        return $pausedOrder->all($where);
    }

    /**
     * @return PagoPuntoVenta[]
     */
    public function getPayments(): array
    {
        $pago = new PagoPuntoVenta();
        $where = [new DataBaseWhere('idsesion', $this->idsesion)];

        return $pago->all($where, [], 0, 0);
    }

    /**
     * @return array
     */
    public function getPaymentsAmount(): array
    {
        $result = [];
        foreach ($this->getPayments() as $pago) {
            if (array_key_exists($pago->codpago, $result)) {
                $result[$pago->codpago]['total'] += $pago->pagoNeto();
            } else {
                $result[$pago->codpago]['total'] = $pago->pagoNeto();
                $result[$pago->codpago]['descripcion'] = $pago->descripcion();
            }
        }

        return $result;
    }

    /**
     * @return TerminalPuntoVenta
     */
    public function getTerminal(): TerminalPuntoVenta
    {
        $terminal = new TerminalPuntoVenta();
        $terminal->loadFromCode($this->idterminal);

        return $terminal;
    }

    /**
     * @param string $nickname
     * @return bool
     */
    public function getUserSession(string $nickname): bool
    {
        $where = [
            new DataBaseWhere('nickusuario', $nickname, '='),
            new DataBaseWhere('abierto', true, '=')
        ];

        return $this->loadFromCode('', $where);
    }

    public function openSession(TerminalPuntoVenta $terminal, float $amount, string $nick): bool
    {
        $this->abierto = true;
        $this->idterminal = $terminal->idterminal;
        $this->nickusuario = $nick;
        $this->saldoinicial = $amount;
        $this->saldoesperado = $amount;

        return $this->save();
    }

    /**
     * @param BusinessDocument $document
     * @param $order
     * @return bool
     */
    public function saveOrder(BusinessDocument $document, $order): bool
    {
        $order->codigo = $document->codigo;
        $order->codcliente = $document->codcliente;
        $order->fecha = $document->fecha;
        $order->iddocumento = $document->primaryColumnValue();
        $order->idsesion = $this->idsesion;
        $order->tipodoc = $document->modelClassName();
        $order->total = $document->total;

        return $order->save();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function updateUser(User $user): bool
    {
        $this->nickusuario = $user->nick;

        return $this->save();
    }
}
