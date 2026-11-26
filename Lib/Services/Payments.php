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
use FacturaScripts\Plugins\POS\Enum\PaymentPolicy;
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
    ): ?ReciboCliente
    {
        return $this->saveInvoiceReceiptData(
            $invoice,
            (string)$payment->codpago,
            $payment->pagoNeto(),
            $number
        );
    }

    public function saveInvoiceReceiptData(
        SalesDocument $invoice,
        string $paymentMethod,
        float $amount,
        int $number = 1
    ): ?ReciboCliente
    {
        if ('FacturaCliente' !== $invoice->modelClassName()) {
            return null;
        }

        $receipt = new ReciboCliente();
        $receipt->codcliente = $invoice->codcliente;
        $receipt->coddivisa = $invoice->coddivisa;
        $receipt->idempresa = $invoice->idempresa;
        $receipt->idfactura = $invoice->id();
        $receipt->importe = $amount;
        $receipt->nick = $invoice->nick;
        $receipt->numero = $number;
        $receipt->fecha = $invoice->fecha;
        $receipt->observaciones = 'pos-payment';
        $receipt->setPaymentMethod($paymentMethod);
        $receipt->pagado = true;
        if (false === $receipt->save()) {
            throw new RuntimeException('payment-receipt-save-error');
        }

        return $receipt;
    }

    /**
     * Replaces the automatically generated invoice receipts with the POS
     * payment-method breakdown.
     *
     * @param array<string, float> $amountsByMethod
     */
    public function replaceInvoiceReceipts(
        SalesDocument $invoice,
        array $amountsByMethod,
        bool $addOutstanding = false
    ): void {
        if ('FacturaCliente' !== $invoice->modelClassName()) {
            return;
        }

        $this->cleanInvoiceReceipts($invoice);
        $counter = 1;
        $collected = 0.0;

        foreach ($amountsByMethod as $paymentMethod => $amount) {
            if (abs((float)$amount) < 0.000001) {
                continue;
            }
            $this->saveInvoiceReceiptData(
                $invoice,
                (string)$paymentMethod,
                (float)$amount,
                $counter++
            );
            $collected += (float)$amount;
        }

        $decimals = (new Currencies())->getDecimals();
        $outstanding = round((float)$invoice->total - $collected, $decimals);
        if ($addOutstanding && $outstanding > 0.0) {
            $receipt = new ReciboCliente();
            $receipt->codcliente = $invoice->codcliente;
            $receipt->coddivisa = $invoice->coddivisa;
            $receipt->idempresa = $invoice->idempresa;
            $receipt->idfactura = $invoice->id();
            $receipt->importe = $outstanding;
            $receipt->nick = $invoice->nick;
            $receipt->numero = $counter;
            $receipt->fecha = $invoice->fecha;
            $receipt->observaciones = 'pos-payment-pending';
            $receipt->setPaymentMethod($invoice->codpago);
            $receipt->pagado = false;
            $receipt->liquidado = 0.0;
            $receipt->fechapago = null;

            if (!$receipt->save()) {
                throw new RuntimeException('payment-receipt-save-error');
            }
        }

        /** @var FacturaCliente $invoice */
        (new ReceiptGenerator())->update($invoice);
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
    public function savePayments(SalesDocument $document, OrdenPuntoVenta $orden, array $payments): bool
    {
        $cashAmount = 0.0;
        $amountsByMethod = [];
        $tracking = new PaymentTracking();

        foreach ($payments as $payment) {
            if ($payment->isCashMethod) {
                $cashAmount += $payment->pagoNeto();
            }

            $payment->idoperacion = $orden->idoperacion;
            $payment->idsesion = $orden->idsesion;
            if (false === $payment->save()) {
                return false;
            }

            $tracking->registerInitial($document, $payment);
            $amountsByMethod[$payment->codpago] =
                ($amountsByMethod[$payment->codpago] ?? 0.0) + $payment->pagoNeto();
        }

        $this->replaceInvoiceReceipts(
            $document,
            $amountsByMethod,
            $orden->payment_policy === PaymentPolicy::OPTIONAL->value
        );

        // Update session cash balance if session is available
        if ($this->session !== null) {
            $this->session->saldoesperado += $cashAmount;
            return $this->session->save();
        }

        return true;
    }
}
