<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Session;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Dinamic\Model\User;

/**
 * Sesion en la que se registran las operaciones de las terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class SesionPuntoVenta extends Base\ModelClass
{
    use Base\ModelTrait;

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

    public $open;

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

    public function clear()
    {
        parent::clear();

        $this->abierto = false;
        $this->fechainicio = date(self::DATE_STYLE);
        $this->horainicio = date(self::HOUR_STYLE);
        $this->nickusuario = false;
        $this->open = false;
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
    public function getCashMovments(): array
    {
        $operacion = new MovimientoPuntoVenta();
        $where = [new DataBaseWhere('idsesion', $this->idsesion)];

        return $operacion->all($where);
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
        $this->fechafin = date(self::DATE_STYLE);
        $this->horafin = date(self::HOUR_STYLE);
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
}
