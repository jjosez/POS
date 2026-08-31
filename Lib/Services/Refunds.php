<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\DocTransformation;
use FacturaScripts\Dinamic\Model\MovimientoPuntoVenta;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;

class Refunds
{
    private SesionPuntoVenta $session;
    private TerminalPuntoVenta $terminal;
    private PaymentValidator $paymentValidator;

    public function __construct(
        SesionPuntoVenta $session,
        TerminalPuntoVenta $terminal,
        PaymentValidator $paymentValidator
    )
    {
        $this->session = $session;
        $this->terminal = $terminal;
        $this->paymentValidator = $paymentValidator;
    }

    public function processRefund(
        OrdenPuntoVenta $originalOrder,
        array $refundLines,
        array $payments
    ): array {
        $originalDoc = $originalOrder->getDocument();
        $modelClass = $originalDoc->modelClassName();

        $newDoc = $this->createRefundDocument($originalDoc, $modelClass);
        $lineRefs = $this->createRefundLines($newDoc, $originalDoc, $refundLines);

        if (false === Calculator::calculate($newDoc, $lineRefs['lines'], false)) {
            throw new \RuntimeException('refund-calculate-error');
        }

        $payments = $this->paymentValidator->validate(
            $payments,
            (float)$newDoc->total,
            PaymentValidator::REFUND
        );

        $newDoc->codpago = $this->getPrimaryPaymentMethod($payments);
        if (false === $newDoc->save()) {
            throw new \RuntimeException('refund-document-save-error');
        }

        // Recreate lines now that the refund document has its primary key.
        $lineRefs = $this->createRefundLines($newDoc, $originalDoc, $refundLines);
        if (false === Calculator::calculate($newDoc, $lineRefs['lines'], true)) {
            throw new \RuntimeException('refund-calculate-error');
        }

        $this->createDocTransformations($originalDoc, $newDoc, $lineRefs['mapping']);

        $refundOrder = $this->createRefundOrder($originalOrder, $newDoc);

        $this->processRefundPayments($newDoc, $refundOrder, $payments);

        $this->recordRefundMovement($newDoc, $refundOrder);

        return [
            'document' => $newDoc->toArray(true),
            'order' => $refundOrder->toArray(true),
        ];
    }

    private function createRefundDocument(SalesDocument $originalDoc, string $modelClass): SalesDocument
    {
        $className = '\\FacturaScripts\\Dinamic\\Model\\' . $modelClass;

        /** @var SalesDocument $newDoc */
        $newDoc = new $className();
        $newDoc->loadFromData($originalDoc->toArray(), $newDoc::dontCopyFields());

        $newDoc->codserie = $this->resolveRefundSerie($originalDoc);
        $newDoc->fecha = Tools::date();
        $newDoc->hora = Tools::hour();
        $newDoc->nick = $this->session->nickusuario;

        if ('FacturaCliente' === $modelClass) {
            $newDoc->idfacturarect = $originalDoc->idfactura;
            $newDoc->codigorect = $originalDoc->codigo;
        }

        return $newDoc;
    }

    private function createRefundLines(
        SalesDocument $newDoc,
        SalesDocument $originalDoc,
        array $refundLines
    ): array {
        $originalLines = $originalDoc->getLines();
        $originalIndex = [];

        foreach ($originalLines as $line) {
            $originalIndex[$line->idlinea] = $line;
        }

        $lines = [];
        $mapping = [];
        foreach ($refundLines as $refundLine) {
            $idlinea = $refundLine['idlinea'];
            $quantity = -abs((float)($refundLine['cantidad'] ?? 0));

            if (0 === $quantity || !isset($originalIndex[$idlinea])) {
                continue;
            }

            $originalLine = $originalIndex[$idlinea];
            $lineData = $originalLine->toArray();
            $lineData['cantidad'] = $quantity;

            $newLine = $newDoc->getNewLine($lineData);

            if ('FacturaCliente' === $newDoc->modelClassName()) {
                $newLine->idlinearect = (int)$idlinea;
            }

            $lines[] = $newLine;
            $mapping[] = [
                'original_idlinea' => (int)$idlinea,
                'cantidad' => abs($quantity),
            ];
        }

        return ['lines' => $lines, 'mapping' => $mapping];
    }

    private function createDocTransformations(
        SalesDocument $originalDoc,
        SalesDocument $newDoc,
        array $mapping
    ): void {
        if (empty($mapping)) {
            return;
        }

        $newLines = $newDoc->getLines();
        $newIndex = [];

        foreach ($newLines as $newLine) {
            $ref = $newLine->referencia ?? '';
            $newIndex[$ref] = $newLine;
        }

        foreach ($mapping as $i => $map) {
            if (!isset($newLines[$i])) {
                continue;
            }

            $trans = new DocTransformation();
            $trans->model1 = $originalDoc->modelClassName();
            $trans->iddoc1 = $originalDoc->id();
            $trans->idlinea1 = $map['original_idlinea'];
            $trans->model2 = $newDoc->modelClassName();
            $trans->iddoc2 = $newDoc->id();
            $trans->idlinea2 = $newLines[$i]->idlinea ?? null;
            $trans->cantidad = $map['cantidad'];
            if (false === $trans->save()) {
                throw new \RuntimeException('refund-transformation-save-error');
            }
        }
    }

    private function createRefundOrder(
        OrdenPuntoVenta $originalOrder,
        SalesDocument $newDoc
    ): OrdenPuntoVenta {
        $refundOrder = new OrdenPuntoVenta();

        $refundOrder->codigo = $newDoc->codigo;
        $refundOrder->codcliente = $newDoc->codcliente;
        $refundOrder->fecha = $newDoc->fecha;
        $refundOrder->hora = $newDoc->hora;
        $refundOrder->iddocumento = $newDoc->id();
        $refundOrder->idsesion = $this->session->idsesion;
        $refundOrder->tipodoc = $newDoc->modelClassName();
        $refundOrder->total = $newDoc->total;
        $refundOrder->esdevolucion = true;
        $refundOrder->idoperacion_original = $originalOrder->idoperacion;

        if (false === $refundOrder->save()) {
            throw new \RuntimeException('refund-order-save-error');
        }

        return $refundOrder;
    }

    private function processRefundPayments(
        SalesDocument $document,
        OrdenPuntoVenta $refundOrder,
        array $payments
    ): void
    {
        $cashAmount = 0.0;
        $counter = 1;
        $paymentService = new Payments();
        $paymentService->cleanInvoiceReceipts($document);

        foreach ($payments as $paymentData) {
            $payment = new PagoPuntoVenta();

            $payment->cantidad = $paymentData['amount'];
            $payment->cambio = $paymentData['change'] ?? 0.0;
            $payment->codpago = $paymentData['method'];
            $payment->isCashMethod = $paymentData['is_cash'];
            $payment->idoperacion = $refundOrder->idoperacion;
            $payment->idsesion = $refundOrder->idsesion;

            if (false === $payment->save()) {
                throw new \RuntimeException('refund-payment-save-error');
            }

            $paymentService->saveInvoiceReceipt($document, $payment, $counter++);

            if ($payment->isCashMethod) {
                $cashAmount += $payment->pagoNeto();
            }
        }

        if ($cashAmount < 0 && $this->session) {
            $this->session->saldoesperado += $cashAmount;

            if (false === $this->session->save()) {
                throw new \RuntimeException('refund-balance-update-error');
            }
        }
    }

    private function getPrimaryPaymentMethod(array $payments): string
    {
        $method = '';
        $net = 0.0;

        foreach ($payments as $payment) {
            if (abs($payment['net']) > $net) {
                $method = $payment['method'];
                $net = abs($payment['net']);
            }
        }

        return $method;
    }

    private function recordRefundMovement(SalesDocument $newDoc, OrdenPuntoVenta $refundOrder): void
    {
        $movement = new MovimientoPuntoVenta();
        $movement->idsesion = $this->session->idsesion;
        $movement->nickusuario = $this->session->nickusuario;
        $movement->descripcion = Tools::trans(
            'refund-document',
            ["%document%" => $newDoc->codigo, "%amount%" => $newDoc->total]
        );
        $movement->total = $newDoc->total;

        if (false === $movement->save()) {
            throw new \RuntimeException('refund-movement-save-error');
        }
    }

    private function resolveRefundSerie(SalesDocument $originalDoc): string
    {
        return $this->terminal->codserierect ?: $originalDoc->codserie;
    }
}
