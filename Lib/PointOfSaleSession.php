<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Dinamic\Model\User;

class PointOfSaleSession
{
    const POS_SESSION_ID = 'POS_SESSION_ID';
    const POS_TERMINAL_ID = 'POS_TERMINAL_ID';
    const POS_TERMINAL = 'POS_SESSION_TERMINAL';
    const POS_SESSION = 'POS_SESSION';

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

        Session::set(self::POS_SESSION_ID, $this->session->idsesion);
        Session::set(self::POS_SESSION, $this->session);
        $this->loadTerminal($this->session->idterminal);
    }

    protected function loadTerminal(string $code): bool
    {
        if (false === $this->terminal->loadFromCode($code)) {
            Tools::log('POS')->warning('cash-register-not-found');
            return false;
        }

        Session::set(self::POS_TERMINAL_ID, $this->terminal->idterminal);
        Session::set(self::POS_TERMINAL, $this->terminal);
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

    public function open(string $terminal, float $amount = 0.0)
    {
        if (true === $this->isOpen()) {
            Tools::log('POS')->info('till-session-allready-opened', ['%userNickname%' => $this->user->nick]);
            return;
        }

        if ($this->loadTerminal($terminal) && $this->session->open($this->terminal, $amount, $this->user)) {
            $params = [
                '%terminalName%' => $this->terminal->nombre,
                '%userNickname%' => $this->user->nick,
            ];

            Tools::log('POS')->info('till-session-opened', $params);
            Tools::log('POS')->info('cashup-total', ['%amount%' => $amount]);

            return;
        }

        Tools::log()->info('error');
    }

    /**
     * Close current session.
     */
    public function closeSession(array $coinsCount): bool
    {
        if (false === $this->isOpen()) {
            Tools::log('POS')->info('till-session-not-opened');
            return false;
        }

        if ($this->session->close($this->terminal, $coinsCount)) {
            Tools::log('POS')->info('cashup-total', ['%amount%' => $this->session->saldocontado]);

            Session::set(self::POS_SESSION_ID, null);
            Session::set(self::POS_TERMINAL_ID, null);
            return true;
        }

        Tools::log('POS')->info('error-closing-pos-session');

        return false;
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

    public static function getSessionID()
    {
        return Session::get(self::POS_SESSION_ID);
    }

    public static function getSessionModel(): SesionPuntoVenta
    {
        return Session::get(self::POS_SESSION);
    }

    public static function getSessionNick(): string
    {
        return Session::user()->nick;
    }

    public static function getSessionTerminal(?string $terminalID = null): TerminalPuntoVenta
    {
        $terminalID = $terminalID ?:Session::get(self::POS_TERMINAL_ID);

        $terminal = new TerminalPuntoVenta();
        $terminal->loadFromCode($terminalID);

        return $terminal;
    }
}
