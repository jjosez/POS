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

use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\ConciliacionPago;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\PagoCliente;

/**
 * A set of tools to recalculate Point of Sale documents.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class Payments
{
    private $cashPaymentMethod;
    private $cashPaymentAmount;  
    private $document;
    private $payments; 
    private $change;

    public function __construct($cashPaymentMethod, $document, $payments) 
    {
        $this->cashPaymentMethod = $cashPaymentMethod;
        $this->document = $document;
        $this->payments = $payments;      
    }

    public function processPayments()
    {
        $paymentAmount = $payments['amount'];
        $paymentChange = $payments['change'];

        if ($payments['method'] == AppSettings::get('pointofsale','fpagoefectivo') ) {
            $this->arqueo->saldoesperado += (float) ($paymentAmount - $paymentChange);
            $this->arqueo->save(); 
        }

        $newPayment = new PagoCliente();
        $newPayment->cantidad = $paymentAmount - $paymentChange;
        $newPayment->codcliente = $document->codcliente;
        $newPayment->coddivisa = $document->coddivisa;
        $newPayment->codpago = $document->codpago;

        $newPayment->save();
        return true;
    }

    public function getCashPaymentAmount()
    {
        foreach ($this->payments as $payment) {
            if ($payment['method'] == $this->cashPaymentMethod) {
                return (float) ($payment['amount'] - $payment['change']);
            }
        }
        
        return 0.0;
    }
}
