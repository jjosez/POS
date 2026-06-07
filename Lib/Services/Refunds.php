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

    public function __construct(SesionPuntoVenta $session, TerminalPuntoVenta $terminal)
    {
        $this->session = $session;
        $this->terminal = $terminal;
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

        if (false === Calculator::calculate($newDoc, $lineRefs['lines'], true)) {
            throw new \RuntimeException('refund-calculate-error');
        }

        $this->createDocTransformations($originalDoc, $newDoc, $lineRefs['mapping']);

        $refundOrder = $this->createRefundOrder($originalOrder, $newDoc);

        $this->processRefundPayments($refundOrder, $payments);

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

        if (false === $newDoc->save()) {
            throw new \RuntimeException('refund-document-save-error');
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
            $trans->save();
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

    private function processRefundPayments(OrdenPuntoVenta $refundOrder, array $payments): void
    {
        $cashAmount = 0.0;

        foreach ($payments as $paymentData) {
            $payment = new PagoPuntoVenta();

            $payment->cantidad = $paymentData['amount'];
            $payment->cambio = $paymentData['change'] ?? 0.0;
            $payment->codpago = $paymentData['method'];
            $payment->isCashMethod = $paymentData['is_cash'] ?? false;
            $payment->idoperacion = $refundOrder->idoperacion;
            $payment->idsesion = $refundOrder->idsesion;

            if (false === $payment->save()) {
                throw new \RuntimeException('refund-payment-save-error');
            }

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

    private function recordRefundMovement(SalesDocument $newDoc, OrdenPuntoVenta $refundOrder): void
    {
        $movement = new MovimientoPuntoVenta();
        $movement->idsesion = $this->session->idsesion;
        $movement->nickusuario = $this->session->nickusuario;
        $movement->descripcion = 'refund: ' . $newDoc->codigo;
        $movement->total = $newDoc->total;

        $movement->save();
    }

    private function resolveRefundSerie(SalesDocument $originalDoc): string
    {
        return $this->terminal->codserierect ?: $originalDoc->codserie;
    }
}
