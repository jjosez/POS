<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Plugins\PrintTicket\Lib\PrintingService;
use FacturaScripts\Plugins\PrintTicket\Lib\Ticket\Builder\SalesTicketBuilder;

class PointOfSalePrinter
{
    public static function cashupTicket(SesionPuntoVenta $session, Empresa $company, int $width)
    {
        $ticketBuilder = new PointOfSaleClosingVoucher($session, $company, $width);

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
