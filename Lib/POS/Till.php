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

class Till
{
    /**
     * @var SesionPOS
     */
    private $session;

    /**
     * @var OperacionPOS
     */
    private $currentTransaction;


    /**
     * @var bool
     */
    private $opened;

    /**
     * @var TerminalPOS
     */
    private $terminal;


    /**
     * @var User
     */
    private $user;

    /**
     * POS session constructor.
     *
     */
    public function __construct(User $user)
    {
        $this->session = new SesionPOS();
        $this->terminal = new TerminalPOS();
        $this->user = $user;
        $this->opened = true;

        if (!$this->session->isOpen('user', $this->user->nick)) {
            $this->opened = false;
        }

        if (!$this->terminal->loadFromCode($this->session->idterminal)) {
            $this->opened = false;
        }
    }

    /**
     * Close current session.
     *
     * @return void
     */
    public function close(array $cash)
    {
        if (false === $this->opened) {
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
            $this->opened = false;
        }
    }

    /**
     * @return SesionPOS
     */
    public function getSession()
    {
        return $this->session;
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
    public function open(string $idterminal, float $amount)
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

            $this->opened = true;
            return true;
        }
        return false;
    }

    public function transactionRecord(BusinessDocument $document)
    {
        $operation = new OperacionPOS();
        $operation->codigo = $document->codigo;
        $operation->codcliente = $document->codcliente;
        $operation->fecha = $document->fecha;
        $operation->iddocumento = $document->primaryColumnValue();
        $operation->idsesion = $this->session->idsesion;
        $operation->tipodoc = $document->modelClassName();
        $operation->total = $document->total;

        $operation->save();
        $this->currentTransaction = $operation;
    }

    /**
     * Returns all OperacionPOS that for current till.
     *
     * @return OperacionPOS[]
     */
    public function transactionList(): array
    {
        $operation = new OperacionPOS();
        $where = [new DataBaseWhere('idsesion', $this->session->idsesion)];

        return $operation->all($where);
    }

    /**
     * Return all OperacionPausada that is not yet completed.
     *
     * @return OperacionPausada[]
     */
    public function pausedTransactionList(): array
    {
        $operation = new OperacionPausada();
        $where = [new DataBaseWhere('editable', true)];

        return $operation->all($where);
    }

    /**
     * Return OperacionPausada by code.
     *
     * @param string $code
     * @return false|string
     */
    public function pausedTransaction(string $code)
    {
        $transaction = new OperacionPausada();

        if ($transaction->loadFromCode($code)) {
            return json_encode(
                [
                    'doc' => $transaction->toArray(),
                    'lines' => $transaction->getLines()
                ]
            );
        }

        return false;
    }

    public function pausedTransactionComplete(string $code)
    {
        $transaction = new OperacionPausada();

        if ($code && $transaction->loadFromCode($code)) {
            $transaction->idestado = 3;

            $transaction->save();
        }
    }

    public function savePayments(array $payments)
    {
        $processor = new PaymentsProcessor($payments);
        $processor->savePayments($this->currentTransaction, $this->session);

        $this->session->saldoesperado += $processor->getCashPaymentAmount();
        $this->session->save();
    }
}
