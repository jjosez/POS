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
    /**
     * @var
     */
    protected $currentOrder;

    /**
     * @var bool
     */
    private $open;

    /**
     * @var SesionPuntoVenta
     */
    private $session;

    /**
     * @var OrderStorage
     */
    protected $storage;

    /**
     * @var TerminalPuntoVenta
     */
    private $terminal;

    /**
     * @var User
     */
    private $user;

    /**
     * TillSessionHelper constructor.
     */
    public function __construct(User $user)
    {
        $this->session = new SesionPuntoVenta();
        $this->terminal = new TerminalPuntoVenta();

        $this->user = $user;
        $this->open = true;

        if (false === $this->session->isOpen('user', $this->user->nick)) {
            $this->open = false;
        }

        if (false === $this->terminal->loadFromCode($this->session->idterminal)) {
            $this->open = false;
        }

        $this->storage = new OrderStorage($this->session);
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

        $this->session->abierto = false;
        $this->session->fechafin = date('d-m-Y');
        $this->session->horafin = date('H:i:s');

        $total = 0.0;
        foreach ($cash as $value => $count) {
            $total += (float)$value * (float)$count;
        }

        ToolBox::i18nLog()->info('cashup-total', ['%amount%' => $total]);
        $this->session->saldocontado = $total;
        $this->session->conteo = json_encode($cash);

        if ($this->session->save()) {
            $this->terminal->disponible = true;
            $this->terminal->save();
            $this->open = false;
        }
    }

    /**
     * @return SesionPuntoVenta
     */
    public function getSession(): SesionPuntoVenta
    {
        return $this->session;
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
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * Initialize a new till session if not exist.
     *
     * @return bool
     */
    public function open(string $idterminal, float $amount): bool
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

        $this->session->abierto = true;
        $this->session->idterminal = $this->terminal->idterminal;
        $this->session->nickusuario = $this->user->nick;
        $this->session->saldoinicial = $amount;
        $this->session->saldoesperado = $amount;

        if ($this->session->save()) {
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
        $processor->savePayments($this->currentOrder, $this->session);

        $this->session->saldoesperado += $processor->getCashPaymentAmount();
        $this->session->save();
    }

    /**
     * @return OrderStorage
     */
    public function getStorage(): OrderStorage
    {
        return $this->storage;
    }

    public function updateUser(User $user)
    {
        $this->user = $user;

        $this->session->nickusuario = $this->user->nick;
        $this->session->save();
    }
}
