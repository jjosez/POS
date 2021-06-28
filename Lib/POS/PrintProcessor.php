<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Lib\POS;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Lib\PrintingService;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\SesionPOS;
use FacturaScripts\Plugins\PrintTicket\Lib\Ticket\Builder\CashupTicketBuilder;
use FacturaScripts\Plugins\PrintTicket\Lib\Ticket\Builder\SalesTicketBuilder;

/**
 * Class to help with printing sales tickets.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PrintProcessor
{
    public static function printCashup(SesionPOS $session, Empresa $company, int $width)
    {
        $ticketBuilder = new CashupTicketBuilder($session, $company, $width);
        $cashupTicket = new PrintingService($ticketBuilder);

        $cashupTicket->savePrintJob();

        return $cashupTicket->getMessage();
    }

    public static function printDocument(BusinessDocument $document, int $width)
    {
        $ticketBuilder = new SalesTicketBuilder($document, $width);

        $salesTicket = new PrintingService($ticketBuilder);
        $salesTicket->savePrintJob();

        return $salesTicket->getMessage();
    }
}
