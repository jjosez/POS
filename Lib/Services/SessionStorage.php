<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Services;

use Exception;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\LineaFacturaCliente;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\BorradorPuntoVenta;
use FacturaScripts\Dinamic\Model\MovimientoPuntoVenta;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;

/**
 * Unified storage service for POS session data.
 * Handles drafts, orders, and cash movements.
 */
class SessionStorage
{
    private SesionPuntoVenta $session;

    public function __construct(SesionPuntoVenta $session)
    {
        $this->session = $session;
    }

    // ========================================================================
    // DRAFT OPERATIONS
    // ========================================================================

    public function getDraft(string $code): ?BorradorPuntoVenta
    {
        $draft = new BorradorPuntoVenta();
        if ($draft->load($code)) {
            $draft->fecha = Tools::date();
            $draft->hora = Tools::hour();
            return $draft;
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public function getDrafts(): array
    {
        return BorradorPuntoVenta::allOpened();
    }

    public function saveDraft(BorradorPuntoVenta $draft): bool
    {
        $draft->idsesion = $this->session->idsesion;
        $draft->nickusuario = $this->session->nickusuario;

        if ($draft->save()) {
            Tools::log('POS')->info('draft-saved', ['code' => $draft->codigo]);
            return true;
        }

        Tools::log('POS')->error('draft-save-failed');
        return false;
    }

    public function deleteDraft(string $code): bool
    {
        if (empty($code)) {
            return false;
        }

        $draft = new BorradorPuntoVenta();
        if (!$draft->load($code)) {
            return false;
        }

        if ($draft->delete()) {
            Tools::log('POS')->info('draft-deleted', ['%code%' => $draft->codigo]);
            return true;
        }

        return false;
    }

    public function completeDraft(SalesDocument $document): bool
    {
        if (empty($document->idpausada)) {
            return true;
        }

        $draft = new BorradorPuntoVenta();
        if (!$draft->load($document->idpausada)) {
            return false;
        }

        return $draft->setAsCompleted();
    }

    // ========================================================================
    // ORDER OPERATIONS
    // ========================================================================

    public function getOrder(string $code): ?OrdenPuntoVenta
    {
        $order = new OrdenPuntoVenta();
        return $order->load($code) ? $order : null;
    }

    public function getOrders(): array
    {
        return OrdenPuntoVenta::allFromSession($this->session->idsesion);
    }

    public function getOrderFromDocument(string $modelClass, string $code): ?OrdenPuntoVenta
    {
        $order = new OrdenPuntoVenta();
        return $order->loadFromDocument($modelClass, $code) ? $order : null;
    }

    public function getOrderForRefund(string $code): array
    {
        $order = $this->getOrder($code);
        if (!$order) {
            throw new Exception('order-not-found');
        }

        return $this->getRefundData($order);
    }

    public function getRefundData(OrdenPuntoVenta $order): array
    {
        $document = $order->getDocument();

        $lines = [];
        foreach ($document->getLines() as $line) {
            $data = $line->toArray(true);

            if ($line instanceof LineaFacturaCliente) {
                $refunded = $line->refundedQuantity();
                $data['refunded'] = $refunded;
                $data['refundable'] = max(0, ($data['cantidad'] ?? 0) - $refunded);
            } else {
                $data['refunded'] = 0;
                $data['refundable'] = $data['cantidad'] ?? 0;
            }

            $lines[] = $data;
        }

        return [
            'document' => $document->toArray(true),
            'lines' => $lines,
            'idoperacion' => $order->idoperacion,
        ];
    }

    public function saveOrder(OrdenPuntoVenta $order, SalesDocument $document): bool
    {
        $order->codigo = $document->codigo;
        $order->codcliente = $document->codcliente;
        $order->fecha = $document->fecha;
        $order->iddocumento = $document->id();
        $order->idsesion = $this->session->idsesion;
        $order->tipodoc = $document->modelClassName();
        $order->total = $document->total;

        if ($order->save()) {
            Tools::log('POS')->info('pos-order-save-ok', [
                '%code%' => $order->codigo,
                '%total%' => $order->total
            ]);
            return true;
        }

        Tools::log('POS')->error('order-save-failed');
        return false;
    }

    // ========================================================================
    // CASH OPERATIONS
    // ========================================================================

    public function recordCashMovement(float $amount, string $description): bool
    {
        $movement = new MovimientoPuntoVenta();
        $movement->idsesion = $this->session->idsesion;
        $movement->nickusuario = $this->session->nickusuario;
        $movement->descripcion = $description;
        $movement->total = $amount;

        if (!$movement->save()) {
            Tools::log('POS')->error('cash-movement-failed', [
                'amount' => $amount,
                'description' => $description
            ]);
            return false;
        }

        // Update expected balance
        $this->session->saldoesperado += $amount;

        if ($this->session->save()) {
            Tools::log('POS')->info('cash-movement-saved', [
                'amount' => $amount,
                'new_balance' => $this->session->saldoesperado
            ]);
            return true;
        }

        return false;
    }
}
