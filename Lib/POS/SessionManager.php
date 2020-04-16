<?php
/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\OperacionPOS;
use FacturaScripts\Dinamic\Model\SesionPOS;
use FacturaScripts\Dinamic\Model\TerminalPOS;
use FacturaScripts\Dinamic\Model\User;

class SessionManager
{
    private $arqueo;
    private $opened;
    private $terminal;
    private $user;
    private $cart;

    /**
     * TillSessionHelper constructor.
     */
    public function __construct(User $user)
    {
        $this->arqueo = new SesionPOS();
        $this->terminal = new TerminalPOS();
        $this->user = $user;

        $this->opened = true;

        if (!$this->arqueo->isOpen('user', $this->user->nick)) {
            $this->opened = false;
        }

        if (!$this->terminal->loadFromCode($this->arqueo->idterminal)) {
            $this->opened = false;
        }
    }

    /**
     * Close current session.
     *
     * @return void
     */
    public function closeSession(array $cash)
    {
        if (false === $this->opened) {
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

        ToolBox::i18nLog()->info('cashup-money-counted', ['%amount%' => $total]);
        $this->arqueo->saldocontado = $total;
        $this->arqueo->conteo = json_encode($cash);

        if ($this->arqueo->save()) {
            $this->terminal->disponible = true;
            $this->terminal->save();
            $this->opened = false;
        }
    }

    /**
     * @return SesionPOS
     */
    public function getArqueo()
    {
        return $this->arqueo;
    }

    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @return TerminalPOS
     */
    public function getTerminal($idterminal = false)
    {
        if ($this->opened) return $this->terminal;

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
        return $this->opened;
    }

    /**
     * Initialize a new till session if not exist.
     *
     * @return bool
     */
    public function openSession(string $idterminal, float $amount)
    {
        if (true === $this->opened) {
            ToolBox::i18nLog()->info('till-session-allready-opened');
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
            ToolBox::i18nLog()->info('cashup-money-counted', ['%amount%' => $amount]);

            $this->terminal->disponible = false;
            $this->terminal->save();

            $this->opened = true;
            return true;
        }
        return false;
    }

    public function recordTransaction(BusinessDocument $document)
    {
        $trasaction = new OperacionPOS();
        $trasaction->codigo = $document->codigo;
        $trasaction->codcliente = $document->codcliente;
        $trasaction->fecha = $document->fecha;
        $trasaction->iddocumento = $document->primaryColumnValue();
        $trasaction->idsesion = $this->arqueo->idsesion;
        $trasaction->tipodoc = $document->modelClassName();
        $trasaction->total = $document->total;

        return $trasaction->save();
    }
}
