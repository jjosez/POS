<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\User;

/**
 * Sesion en la que se registran las operaciones de las terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class SesionPuntoVenta extends ModelClass
{
    use ModelTrait;

    /**
     * @var bool
     */
    public $abierto;

    /**
     * @var string
     */
    public $conteo;

    /**
     * @var string
     */
    public $fechainicio;

    /**
     * @var string
     */
    public $fechafin;

    /**
     * @var string
     */
    public $horainicio;

    /**
     * @var string
     */
    public $horafin;

    /**
     * @var string
     */
    public $idsesion;

    /**
     * @var string
     */
    public $idterminal;

    /**
     * @var string
     */
    public $nickusuario;

    /**
     * @var float
     */
    public $saldocontado;

    /**
     * @var float
     */
    public $saldoesperado;

    /**
     * @var float
     */
    public $saldoinicial;
    public $saldomovimientos;
    public $saldoretirado;

    public function clear(): void
    {
        parent::clear();

        $this->abierto = false;
        $this->fechainicio = Tools::date();
        $this->horainicio = Tools::hour();
        $this->nickusuario = false;
        $this->saldocontado = 0.0;
        $this->saldoesperado = 0.0;
    }

    public function install(): string
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
     * Returns the operations associated with the sessionpos.
     *
     * @return MovimientoPuntoVenta[]
     */
    public function getCashMovements(): array
    {
        $operacion = new MovimientoPuntoVenta();
        $where = [new DataBaseWhere('idsesion', $this->idsesion)];

        return $operacion->all($where);
    }

    /**
     * Returns the cash entry, withdraw associated with the sessionpos.
     *
     * @return array
     */
    public function getCashMovementsAmount(): array
    {
        $result = [
            'cash-withdraw' => 0,
            'cash-entry' => 0
        ];

        foreach ($this->getCashMovements() as $movment) {
            if ($movment->total < 0) {
                $result['cash-withdraw'] += $movment->total;
            } else {
                $result['cash-entry'] += $movment->total;
            }
        }

        return $result;
    }

    /**
     * @return PagoPuntoVenta[]
     */
    public function getPayments(): array
    {
        return PagoPuntoVenta::all([
            Where::eq('idsesion', $this->idsesion)
        ]);
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
        $terminal->load($this->idterminal);

        return $terminal;
    }

    /**
     * @param string $nickname
     * @return bool
     */
    public function getUserSession(string $nickname): bool
    {
        $where = [
            Where::eq('nickusuario', $nickname),
            Where::eq('abierto', true)
        ];

        return $this->loadWhere($where);
    }

    public function open(TerminalPuntoVenta $terminal, float $amount, User $user): bool
    {
        $this->abierto = true;
        $this->idterminal = $terminal->idterminal;
        $this->nickusuario = $user->nick;
        $this->saldoinicial = $amount;
        $this->saldoesperado = $amount;

        $terminal->disponible = false;

        return $this->save() && $terminal->save();
    }

    public function close(TerminalPuntoVenta $terminal, array $coinsCount): bool
    {
        $totalCounted = 0.0;
        foreach ($coinsCount as $value => $count) {
            $totalCounted += (float)$value * (float)$count;
        }

        $this->abierto = false;
        $this->fechafin = Tools::date();
        $this->horafin = Tools::hour();
        $this->saldocontado = $totalCounted;
        $this->conteo = json_encode($coinsCount);

        $terminal->disponible = true;


        return $this->save() && $terminal->save();
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

    public function delete(): bool
    {
        if ($this->getTerminal()->disponible) {
            Tools::log()->warning('terminal-is-open');

            return false;
        }

        return parent::delete();
    }
}
