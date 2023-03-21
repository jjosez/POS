<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;
use FacturaScripts\Dinamic\Model\ReciboCliente;

class PointOfSalePayments
{
    public static function savePayments(string $cashMethod, OrdenPuntoVenta $order, array $payments): float
    {
        $cashAmount = 0.0;

        $document = $order->getDocument();

        if ('FacturaCliente' === $document->modelClassName()) {
            self::cleanInvoiceReceipts($document);
        }

        $counter = 1;
        foreach ($payments as $payment) {
            if ($payment['method'] === $cashMethod) {
                $cashAmount += $payment['amount'] - $payment['change'];
            }

            $posPayment = new PagoPuntoVenta();

            $posPayment->cantidad = $payment['amount'];
            $posPayment->cambio = $payment['change'];
            $posPayment->codpago = $payment['method'];
            $posPayment->idoperacion = $order->idoperacion;
            $posPayment->idsesion = $order->idsesion;

            if ($posPayment->save()) {
                if ('FacturaCliente' === $document->modelClassName()) {
                    PointOfSalePayments::saveInvoiceReceipt($document, $posPayment, $counter++);
                }
            }
        }

        return $cashAmount;
    }

    public static function cleanInvoiceReceipts(FacturaCliente $invoice)
    {
        foreach ($invoice->getReceipts() as $receipt) {
            $receipt->delete();
        }
    }

    public static function saveInvoiceReceipt(FacturaCliente $invoice, PagoPuntoVenta $payment, int $number = 1)
    {
        $receipt = new ReciboCliente();

        $receipt->codcliente = $invoice->codcliente;
        $receipt->coddivisa = $invoice->coddivisa;
        $receipt->idempresa = $invoice->idempresa;
        $receipt->idfactura = $invoice->idfactura;
        $receipt->importe = $payment->pagoNeto();
        $receipt->nick = $invoice->nick;
        $receipt->numero = $number;
        $receipt->fecha = $invoice->fecha;
        $receipt->setPaymentMethod($payment->codpago);
        $receipt->save();
    }
}
