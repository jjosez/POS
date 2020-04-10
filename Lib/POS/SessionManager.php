<?php


namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\User;

use FacturaScripts\Dinamic\Model\TerminalPOS;
use FacturaScripts\Dinamic\Model\SesionPOS;

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

        if ($idterminal && $this->terminal->loadFromCode($idterminal)) {
            return $this->terminal;
        }

        ToolBox::i18nLog()->warning('cash-register-not-found');
        return new TerminalPOS();
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
        if ($this->opened) {
            ToolBox::i18nLog()->info('till-session-allready-opened');
            return false;
        }

        if (!$this->terminal->loadFromCode($idterminal)) {
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
        }
        return true;
    }
}