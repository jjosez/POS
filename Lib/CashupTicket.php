<?php
namespace FacturaScripts\Plugins\EasyPOS\Lib;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Dinamic\Lib\Ticket\Data\Cashup;
use FacturaScripts\Dinamic\Lib\Ticket\Data\Company;
use FacturaScripts\Dinamic\Lib\Ticket\Template\DefaultCashupTemplate;

class CashupTicket
{
    private $company;
    private $session;
    private $template;

    public function __construct($session, $company, int $width = null, CashupTemplate $template = null)
    {
        $this->company = $company;
        $this->session = $session;
        $width = $width ?: $this->getDefaultWitdh();

        $this->template = $template ?: new DefaultCashupTemplate($width);
    }

    public function getTicket()
    {
        $company = new Company(
            $this->company->nombrecorto,
            $this->company->cifnif,
            $this->company->direccion
        );

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

        return $this->template->buildTicket($cashup, $company);
    }

    private function getDefaultWitdh()
    {
        return AppSettings::get('ticket', 'linelength', 50);
    }
}
