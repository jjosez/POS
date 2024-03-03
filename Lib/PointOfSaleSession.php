<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Dinamic\Model\User;

class PointOfSaleSession
{
    /**
     * @var OrdenPuntoVenta
     */
    protected $lastOrder;

    /**
     * @var SesionPuntoVenta
     */
    protected $session;

    /**
     * @var TerminalPuntoVenta
     */
    protected $terminal;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->terminal = new TerminalPuntoVenta();
        $this->session = new SesionPuntoVenta();

        $this->loadSession($user->nick);
    }

    protected function loadSession(string $nick): void
    {
        if (false === $this->session->getUserSession($nick)) {
            return;
        }

        $this->loadTerminal($this->session->idterminal);
    }

    protected function loadTerminal(string $code): bool
    {
        if (false === $this->terminal->loadFromCode($code)) {
            Tools::log()->warning('cash-register-not-found');
            return false;
        }

        return true;
    }

    /**
     * Get POS view if session is allready open or Open View.
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->isOpen() ? '/Block/POS/Main' : '/Block/POS/Login';
    }

    /**
     * Return true if session is open, false otherwise.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->session->abierto && $this->session->nickusuario;
    }

    public function openSession(string $terminal, float $amount = 0.0)
    {
        if (true === $this->session->abierto) {
            ToolBox::i18nLog()->info('till-session-allready-opened', [
                    '%userNickname%' => $this->user->nick
                ]);
            return;
        }

        if (false === $this->loadTerminal($terminal)) {
            return;
        }

        if ($this->session->openSession($this->terminal, $amount, $this->user->nick)) {
            $params = [
                '%terminalName%' => $this->terminal->nombre,
                '%userNickname%' => $this->user->nick,
            ];
            ToolBox::i18nLog()->info('till-session-opened', $params);
            ToolBox::i18nLog()->info('cashup-total', ['%amount%' => $amount]);

            $this->terminal->disponible = false;
            $this->terminal->save();

            return;
        }

        ToolBox::i18nLog()->info('error');
    }

    /**
     * Close current session.
     */
    public function closeSession(array $cash): bool
    {
        if (false === $this->session->abierto) {
            ToolBox::i18nLog()->info('till-session-not-opened');
            return false;
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

            return true;
        }

        return false;
    }

    /**
     * @param SalesDocument $document
     * @return bool
     */
    public function saveOrder(SalesDocument $document): bool
    {
        $this->lastOrder = new OrdenPuntoVenta();

        $this->lastOrder->codigo = $document->codigo;
        $this->lastOrder->codcliente = $document->codcliente;
        $this->lastOrder->fecha = $document->fecha;
        $this->lastOrder->iddocumento = $document->primaryColumnValue();
        $this->lastOrder->idsesion = $this->session->primaryColumnValue();
        $this->lastOrder->tipodoc = $document->modelClassName();
        $this->lastOrder->total = $document->total;

        return $this->lastOrder->save();
    }

    /**
     * @param SalesDocument $document
     * @param PagoPuntoVenta[] $payments
     * @return void
     */
    public function savePayments(SalesDocument $document, array $payments)
    {
        PointOfSalePayments::cleanInvoiceReceipts($document);
        $cashMethodCode = $this->getTerminal()->getCashPaymentMethod();

        $counter = 1;
        $cashAmount = 0;
        foreach ($payments as $payment) {
            if ($cashMethodCode === $payment->codpago) {
                $this->getSession()->saldoesperado += $payment->pagoNeto();
            }

            $payment->idoperacion = $this->getLastOrder()->idoperacion;
            $payment->idsesion = $this->getID();

            if ($payment->save()) {
                PointOfSalePayments::saveInvoiceReceipt($document, $payment, $counter++);
            }
        }

        $this->getSession()->saldoesperado += $cashAmount;
        $this->getSession()->save();
    }

    /**
     * Return current session terminal.
     *
     * @param string $id
     * @return TerminalPuntoVenta
     */
    public function getTerminal(string $id = ''): TerminalPuntoVenta
    {
        if (false === empty($id) && false === $this->isOpen()) {
            $this->loadTerminal($id);
        }

        return $this->terminal;
    }

    public function getLastOrder(): OrdenPuntoVenta
    {
        return $this->lastOrder;
    }

    /**
     * Return current SesionPuntoVenta ID.
     *
     * @return string
     */
    public function getID(): string
    {
        return $this->session->idsesion;
    }

    /**
     * Return current user SesionPuntoVenta.
     *
     * @return SesionPuntoVenta
     */
    public function getSession(): SesionPuntoVenta
    {
        return $this->session;
    }

    /**
     * Replace current user by the given one.
     *
     * @param User $user
     * @return void
     */
    public function updateUser(User $user)
    {
        $this->user = $user;

        $this->session->nickusuario = $this->user->nick;
        $this->session->save();
    }
}
