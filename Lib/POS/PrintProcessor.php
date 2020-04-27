<?php
/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

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
    private $ticket;

    public function __construct()
    {
        $this->ticket = new Ticket;
    }

    public function printCashup(SesionPOS $session, $company)
    {
        $cashupTicket = new CashupTicket($session, $company);
        $printID = 'cashup';

        $this->ticket->coddocument = $printID;
        $this->ticket->text = $cashupTicket->getTicket();

        if ($this->ticket->save()) return true;

        return false;
    }

    public function printDocument(BusinessDocument $document)
    {
        $documentTicket = new BusinessDocumentTicket($document);
        $printID = $document->modelClassName();

        $this->ticket->coddocument = $printID;
        $this->ticket->text = $documentTicket->getTicket();

        if ($this->ticket->save()) return true;

        return false;
    }
}
