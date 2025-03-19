<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;
use FacturaScripts\Dinamic\Model\ReciboCliente;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;

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

    /**
     * @param SalesDocument $document
     * @param OrdenPuntoVenta $orden
     * @param SesionPuntoVenta $session
     * @param PagoPuntoVenta[] $payments
     * @return bool
     */
    public static function savePayments(
        SalesDocument $document,
        OrdenPuntoVenta $orden,
        SesionPuntoVenta $session,
        array $payments
    ): bool
    {
        self::cleanInvoiceReceipts($document);

        $counter = 1;
        $cashAmount = 0.0;
        foreach ($payments as $payment) {
            if ($payment->isCashMethod) {
                $cashAmount += $payment->pagoNeto();
            }

            $payment->idoperacion = $orden->idoperacion;
            $payment->idsesion = $orden->idsesion;

            if (false === $payment->save()) {
                return false;
            }

            self::saveInvoiceReceipt($document, $payment, $counter++);
        }

        $session->saldoesperado += $cashAmount;

        return $session->save();
    }
}
