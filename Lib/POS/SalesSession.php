<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\POS;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Lib\POS\Sales\OrderStorage;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Dinamic\Model\User;

class SalesSession
{
    private $arqueo;

    protected $currentOrder;

    private $open;
    private $terminal;
    private $user;

    /**
     * @var OrderStorage
     */
    protected $sessionStorage;

    /**
     * TillSessionHelper constructor.
     */
    public function __construct(User $user)
    {
        $this->arqueo = new SesionPuntoVenta();
        $this->terminal = new TerminalPuntoVenta();

        $this->sessionStorage = new OrderStorage($this->arqueo);

        $this->user = $user;
        $this->open = true;

        if (!$this->arqueo->isOpen('user', $this->user->nick)) {
            $this->open = false;
        }

        if (!$this->terminal->loadFromCode($this->arqueo->idterminal)) {
            $this->open = false;
        }
    }

    /**
     * Close current session.
     *
     * @return void
     */
    public function close(array $cash)
    {
        if (false === $this->open) {
            ToolBox::i18nLog()->info('there-is-no-open-till-session');
            return;
        }

        $this->arqueo->abierto = false;
        $this->arqueo->fechafin = date('d-m-Y');
        $this->arqueo->horafin = date('H:i:s');

        $total = 0.0;
        foreach ($cash as $value => $count) {
            $total += (float)$value * (float)$count;
        }

        ToolBox::i18nLog()->info('cashup-total', ['%amount%' => $total]);
        $this->arqueo->saldocontado = $total;
        $this->arqueo->conteo = json_encode($cash);

        if ($this->arqueo->save()) {
            $this->terminal->disponible = true;
            $this->terminal->save();
            $this->open = false;
        }
    }

    /**
     * @return SesionPuntoVenta
     */
    public function getArqueo()
    {
        return $this->arqueo;
    }

    /**
     * @return TerminalPuntoVenta
     */
    public function terminal($idterminal = false)
    {
        if ($this->open) return $this->terminal;

        if ($idterminal && !$this->terminal->loadFromCode($idterminal)) {
            ToolBox::i18nLog()->warning('cash-register-not-found');
        }

        return $this->terminal;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return $this->open;
    }

    /**
     * Initialize a new till session if not exist.
     *
     * @return bool
     */
    public function open(string $idterminal, float $amount)
    {
        if (true === $this->open) {
            $params = ['%userNickname%' => $this->user->nick];
            ToolBox::i18nLog()->info('till-session-allready-opened', $params);
            return false;
        }

        if (false === $this->terminal->loadFromCode($idterminal)) {
            ToolBox::i18nLog()->warning('cash-register-not-found');
            return false;
        }

        $this->arqueo->abierto = true;
        $this->arqueo->idterminal = $this->terminal->idterminal;
        $this->arqueo->nickusuario = $this->user->nick;
        $this->arqueo->saldoinicial = $amount;
        $this->arqueo->saldoesperado = $amount;

        if ($this->arqueo->save()) {
            $params = [
                '%terminalName%' => $this->terminal->nombre,
                '%userNickname%' => $this->user->nick,
            ];
            ToolBox::i18nLog()->info('till-session-opened', $params);
            ToolBox::i18nLog()->info('cashup-total', ['%amount%' => $amount]);

            $this->terminal->disponible = false;
            $this->terminal->save();

            $this->open = true;
            return true;
        }
        return false;
    }

    protected function savePayments(array $payments)
    {
        $processor = new PaymentsProcessor($payments);
        $processor->savePayments($this->currentOrder, $this->arqueo);

        $this->arqueo->saldoesperado += $processor->getCashPaymentAmount();
        $this->arqueo->save();
    }

    /**
     * @return OrderStorage
     */
    public function getStorage(): OrderStorage
    {
        return $this->sessionStorage;
    }
}
