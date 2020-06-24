<?php
/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTicket;
use FacturaScripts\Dinamic\Lib\CashupTicket;
use FacturaScripts\Dinamic\Model\SesionPOS;
use FacturaScripts\Dinamic\Model\Ticket;

/**
 * Class to help with printing sales tickets.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PrintProcessor
{
    public static function printCashup(SesionPOS $session, $company, float $anchopapel)
    {
        $cashupTicket = new CashupTicket($session, $company, $anchopapel);
        $ticket = new Ticket();
        $printID = 'cashup';

        $ticket->coddocument = $printID;
        $ticket->text = $cashupTicket->getTicket();

        if ($ticket->save()) return true;

        return false;
    }

    public static function printDocument(BusinessDocument $document, float $anchopapel)
    {
        $printID = $document->modelClassName();
        $documentTicket = new BusinessDocumentTicket($document, $printID, $anchopapel);

        $ticket = new Ticket();
        $ticket->coddocument = $printID;
        $ticket->text = $documentTicket->getTicket();

        if ($ticket->save()) return true;

        return false;
    }
}
