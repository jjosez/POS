<?php
namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Dinamic\Lib\Ticket\Data\Cashup;
use FacturaScripts\Dinamic\Lib\Ticket\Template\CashupTemplate;
use FacturaScripts\Dinamic\Model\Empresa;

class CashupTicket
{
    private $session;
    private $template;

    public function __construct($session, Empresa $empresa, int $width, CashupTemplate $template = null)
    {
        $this->session = $session;
        $this->template = $template ?: new CashupTemplate($empresa, $width);
    }

    public function getTicket()
    {
        $cashup = new Cashup(
            $this->session->idsesion,
            $this->session->saldoinicial,
            $this->session->saldoesperado,
            $this->session->saldocontado,
            null
        );

        foreach ($this->session->getPagosTotales() as $total) {
            $cashup->addPayment($total['descripcion'], $total['total']);
        }

        return $this->template->buildTicket($cashup);
    }
}
