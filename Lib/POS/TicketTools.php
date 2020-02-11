<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\POS\Lib\POS;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTicket;
use FacturaScripts\Dinamic\Model\Ticket;

/**
 * A set of tools to help with printing jobs.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class TicketTools
{
    private $data;
    private $ticket;
    private $template;       

    public function __construct($data, $template = 'ticket') 
    {
        $this->data = $data;
        $this->template = $template;

        $this->ticket = new Ticket;
    }

    public function printTicket()
    {
        switch ($this->template) {
            case 'ticket':
                $this->printDocumentTicket($this->data); 
                break;

            case 'cashup':
                $this->template = false;
                break;
            
            default:
                $this->template = false;
                break;
        }   
        
    }

    private function printCashupTicket()
    {

    }

    private function printDocumentTicket($document)
    {
        $documentTicket = new BusinessDocumentTicket($document); 
        $printID = $this->data->modelClassName();

        $this->ticket->coddocument = $printID;
        $this->ticket->text = $documentTicket->getTicket(); 

        if ($this->ticket->save()) {
            $msg = '<div class="d-none"><img src="http://localhost:10080?documento=%1s"/></div>';
            (new ToolBox)->log()->info('printing-ticket ' . $document->codigo);
            (new ToolBox)->log()->info(sprintf($msg,  $printID));
            return true;
        }

        (new ToolBox)->log()->warning('error-printing-ticket');
        return false;
    }
}
