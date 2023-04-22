<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;
use FacturaScripts\Dinamic\Model\ReciboCliente;

class PointOfSalePayments
{
    public static function cleanInvoiceReceipts(SalesDocument $invoice)
    {
        if ('FacturaCliente' !== $invoice->modelClassName()) {
            return;
        }

        /** @var FacturaCliente $invoice */
        foreach ($invoice->getReceipts() as $receipt) {
            $receipt->delete();
        }
    }

    public static function saveInvoiceReceipt(SalesDocument $invoice, PagoPuntoVenta $payment, int $number = 1)
    {
        if ('FacturaCliente' !== $invoice->modelClassName()) {
            return;
        }

        $receipt = new ReciboCliente();

        $receipt->codcliente = $invoice->codcliente;
        $receipt->coddivisa = $invoice->coddivisa;
        $receipt->idempresa = $invoice->idempresa;
        $receipt->idfactura = $invoice->primaryColumnValue();
        $receipt->importe = $payment->pagoNeto();
        $receipt->nick = $invoice->nick;
        $receipt->numero = $number;
        $receipt->fecha = $invoice->fecha;
        $receipt->setPaymentMethod($payment->codpago);
        $receipt->save();
    }

    public static function saveInvoiceReceiptFromArray(SalesDocument $invoice, array $payments)
    {
        if ('FacturaCliente' !== $invoice->modelClassName() || empty($payments)) {
            return;
        }

        //Eliminamos el recibo generado automáticamente.
        PointOfSalePayments::cleanInvoiceReceipts($invoice);

        $counter = 1;
        foreach ($payments as $key => $value) {
            ToolBox::log('POS')->warning("CODPAGO: $key  IMPORTE: $value");
            $receipt = new ReciboCliente();

            $receipt->codcliente = $invoice->codcliente;
            $receipt->coddivisa = $invoice->coddivisa;
            $receipt->idempresa = $invoice->idempresa;
            $receipt->idfactura = $invoice->primaryColumnValue();
            $receipt->importe = $value;
            $receipt->nick = $invoice->nick;
            $receipt->numero = $counter++;
            $receipt->fecha = $invoice->fecha;
            $receipt->setPaymentMethod($key);
            $receipt->save();
        }
    }
}
