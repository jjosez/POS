<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Dinamic\Model\User;

class PointOfSaleSession
{
    /**
     * @var SesionPuntoVenta
     */
    protected $session;

    /**
     * @var PointOfSaleStorage
     */
    protected $storage;

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

        $this->loadSession($user->nick);
    }

    protected function loadSession(string $nick): void
    {
        $this->session = new SesionPuntoVenta();
        $this->terminal = new TerminalPuntoVenta();
        $this->storage = new PointOfSaleStorage($this->session);

        if (false === $this->session->loadFromUser($nick)) {
            return;
        }

        $this->loadTerminal($this->session->idterminal);
    }

    protected function loadTerminal(string $id): bool
    {
        if (false === $this->terminal->loadFromCode($id)) {
            ToolBox::i18nLog()->warning('cash-register-not-found');
            return false;
        }

        return true;
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
     * Return current session OrderStorage.
     *
     * @return PointOfSaleStorage
     */
    public function getStorage(): PointOfSaleStorage
    {
        return $this->storage;
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

    public function openSession(string $terminalID, float $amount = 0.0)
    {
        if (true === $this->session->abierto) {
            ToolBox::i18nLog()->info('till-session-allready-opened');
            return;
        }

        if (false === $this->loadTerminal($terminalID)) {
            return;
        }

        $this->session->abierto = true;
        $this->session->idterminal = $terminalID;
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

            return;
        }

        ToolBox::i18nLog()->info('error');
    }

    /**
     * Close current session.
     *
     * @return void
     */
    public function closeSession(array $cash)
    {
        if (false === $this->session->abierto) {
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
