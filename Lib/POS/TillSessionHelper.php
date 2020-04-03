<?php


namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\User;

use FacturaScripts\Dinamic\Model\TerminalPOS;
use FacturaScripts\Dinamic\Model\OperacionPOS;
use FacturaScripts\Dinamic\Model\SesionPOS;

class TillSessionHelper
{
    private $arqueo;
    private $terminal;
    private $toolbox;
    private $user;

    /**
     * TillSessionHelper constructor.
     */
    public function __construct(TerminalPOS $terminal, User $user)
    {
        $this->toolbox =  new ToolBox();
    }

    private function openSessionMessage()
    {
        $this->toolbox->i18nLog()->info('there-is-no-open-till-session');
    }

    private function closeSessionMessage()
    {

    }

}