<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;
use FacturaScripts\Dinamic\Model\ReciboCliente;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use RuntimeException;

/**
 * Service for managing POS payments.
 * Handles payment processing, receipts, and cash management.
 */
class Payments
{
    private ?SesionPuntoVenta $session = null;

    public function __construct(?SesionPuntoVenta $session = null)
    {
        $this->session = $session;
    }

    /**
     * Clean all receipts from an invoice.
     */
    public function cleanInvoiceReceipts(SalesDocument $invoice): void
    {
        if ('FacturaCliente' !== $invoice->modelClassName()) {
            return;
        }

        /** @var FacturaCliente $invoice */
        foreach ($invoice->getReceipts() as $receipt) {
            if (false === $receipt->delete()) {
                throw new RuntimeException('payment-receipt-delete-error');
            }
        }
    }

    /**
     * Save a receipt for an invoice payment.
     */
    public function saveInvoiceReceipt(
        SalesDocument $invoice,
        PagoPuntoVenta $payment,
        int $number = 1
    ): void {
        if ('FacturaCliente' !== $invoice->modelClassName()) {
            return;
        }

        $receipt = new ReciboCliente();

        $receipt->codcliente = $invoice->codcliente;
        $receipt->coddivisa = $invoice->coddivisa;
        $receipt->idempresa = $invoice->idempresa;
        $receipt->idfactura = $invoice->id();
        $receipt->importe = $payment->pagoNeto();
        $receipt->nick = $invoice->nick;
        $receipt->numero = $number;
        $receipt->fecha = $invoice->fecha;
        $receipt->setPaymentMethod($payment->codpago);
        $receipt->pagado = true;
        if (false === $receipt->save()) {
            throw new RuntimeException('payment-receipt-save-error');
        }
    }

    /**
     * Save all payments for an order.
     * Handles receipts and cash balance updates.
     *
     * @param SalesDocument $document
     * @param OrdenPuntoVenta $orden
     * @param PagoPuntoVenta[] $payments
     * @return bool
     */
    public function savePayments(
        SalesDocument $document,
        OrdenPuntoVenta $orden,
        array $payments
    ): bool {
        $this->cleanInvoiceReceipts($document);

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

            $this->saveInvoiceReceipt($document, $payment, $counter++);
        }

        $unpaid = (float)$orden->customer_account_amount;
        if ($orden->payment_policy === PaymentPolicy::OPTIONAL->value) {
            $collected = array_sum(array_map(static fn(PagoPuntoVenta $payment): float => $payment->pagoNeto(), $payments));
            $unpaid = round(max(0, (float)$document->total - $collected), (new Currencies())->getDecimals());
        }
        if ($unpaid > 0 && $document->modelClassName() === 'FacturaCliente') {
            $receipt = new ReciboCliente();
            $receipt->codcliente = $document->codcliente;
            $receipt->coddivisa = $document->coddivisa;
            $receipt->idempresa = $document->idempresa;
            $receipt->idfactura = $document->id();
            $receipt->importe = $unpaid;
            $receipt->nick = $document->nick;
            $receipt->numero = $counter;
            $receipt->fecha = $document->fecha;
            $receipt->setPaymentMethod($document->codpago);
            // Payment terms determine maturity, never whether deferred money was received.
            $receipt->pagado = false;
            $receipt->liquidado = 0.0;
            $receipt->fechapago = null;
            if (!$receipt->save()) {
                throw new RuntimeException('payment-receipt-save-error');
            }
            (new ReceiptGenerator())->update($document);
        }

        // Update session cash balance if session is available
        if ($this->session !== null) {
            $this->session->saldoesperado += $cashAmount;
            return $this->session->save();
        }

        return true;
    }
}
