<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Lib\POS;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\OperacionPausada;
use FacturaScripts\Dinamic\Model\OperacionPOS;
use FacturaScripts\Dinamic\Model\SesionPOS;
use FacturaScripts\Dinamic\Model\TerminalPOS;
use FacturaScripts\Dinamic\Model\User;

class SalesSession
{
    private $arqueo;
    private $cart;
    private $lastOperation;
    private $opened;
    private $terminal;
    private $user;

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
            ToolBox::i18nLog()->info('cashup-money-counted', ['%amount%' => $amount]);

            $this->terminal->disponible = false;
            $this->terminal->save();

            $this->opened = true;
            return true;
        }
        return false;
    }

    public function recordOperation(BusinessDocument $document)
    {
        $operation = new OperacionPOS();
        $operation->codigo = $document->codigo;
        $operation->codcliente = $document->codcliente;
        $operation->fecha = $document->fecha;
        $operation->iddocumento = $document->primaryColumnValue();
        $operation->idsesion = $this->arqueo->idsesion;
        $operation->tipodoc = $document->modelClassName();
        $operation->total = $document->total;

        $operation->save();
        $this->lastOperation = $operation;
    }

    public function loadHistory()
    {
        $operation = new OperacionPOS();
        $where = [new DataBaseWhere('idsesion', $this->arqueo->idsesion)];
        $result = $operation->all($where);

        return $result;
    }

    public function loadPausedOps()
    {
        $pausedops = new OperacionPausada();
        $where = [new DataBaseWhere('editable', true)];
        $result = $pausedops->all($where);

        return $result;
    }

    public function loadPausedOperation(string $code)
    {
        $result = [];

        $pausedop = new OperacionPausada();
        $pausedop->loadFromCode($code);

        $result['doc'] = $pausedop->toArray();
        $result['lines'] = $pausedop->getLines();

        return json_encode($result);
    }

    public function updatePausedOperation(string $code)
    {
        $pausedop = new OperacionPausada();

        if ($code && $pausedop->loadFromCode($code)) {
            $pausedop->idestado = 3;

            $pausedop->save();
        }
    }

    public function savePayments(array $payments)
    {
        $processor = new PaymentsProcessor($payments);
        $processor->savePayments($this->lastOperation, $this->arqueo);

        $this->arqueo->saldoesperado += $processor->getCashPaymentAmount();
        $this->arqueo->save();
    }
}
