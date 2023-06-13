<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Base\SalesDocument;
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
            ToolBox::i18nLog()->warning('cash-register-not-found');
            return false;
        }

        return true;
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

    /**
     * Return true if session is open, false otherwise.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->session->abierto && $this->session->nickusuario;
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

    public function openSession(string $terminal, float $amount = 0.0)
    {
        if (true === $this->session->abierto) {
            ToolBox::i18nLog()->info('till-session-allready-opened');
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
     *
     * @return bool
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

    public function getLastOrder(): OrdenPuntoVenta
    {
        return $this->lastOrder;
    }

    /**
     * @param SalesDocument $document
     * @return bool
     */
    public function saveOrder(SalesDocument $document): bool
    {
        $this->lastOrder = new OrdenPuntoVenta();

        if (false === $this->session->saveOrder($document, $this->lastOrder)) {
            return false;
        }

        return true;
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
                $cashAmount += $payment->pagoNeto();
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
