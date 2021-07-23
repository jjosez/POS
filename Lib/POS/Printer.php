<?php


namespace FacturaScripts\Plugins\POS\Lib\POS;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\SesionPOS;
use FacturaScripts\Plugins\PrintTicket\Lib\PrintingService;
use FacturaScripts\Plugins\PrintTicket\Lib\Ticket\Builder\CashupTicketBuilder;
use FacturaScripts\Plugins\PrintTicket\Lib\Ticket\Builder\SalesTicketBuilder;

class Printer
{
    public static function cashupTicket(SesionPOS $session, Empresa $company, int $width)
    {
        $ticketBuilder = new CashupTicketBuilder($session, $company, $width);

        $cashupTicket = new PrintingService($ticketBuilder);
        $cashupTicket->savePrintJob();

        return $cashupTicket->getMessage();
    }

    public static function salesTicket(BusinessDocument $document, int $width)
    {
        $ticketBuilder = new SalesTicketBuilder($document, $width);

        $salesTicket = new PrintingService($ticketBuilder);
        $salesTicket->savePrintJob();

        return $salesTicket->getMessage();
    }

}
