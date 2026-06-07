<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\Session;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;

/**
 * Application Service for managing cash balance in POS sessions.
 * Handles cash movements, payments, and balance calculations.
 */
class CashBalanceManager
{
    private SesionPuntoVenta $session;
    private SessionStateManager $stateManager;

    public function __construct(SesionPuntoVenta $session, SessionStateManager $stateManager)
    {
        $this->session = $session;
        $this->stateManager = $stateManager;
    }

    /**
     * Gets current cash balance as Value Object.
     */
    public function getBalance(): CashBalance
    {
        return new CashBalance(
            $this->session->saldoesperado ?? 0.0,
            $this->session->saldocontado ?? 0.0
        );
    }

    /**
     * Records a sale payment and updates expected balance.
     *
     * @param OrdenPuntoVenta $orden
     * @param PagoPuntoVenta[] $payments
     * @return bool
     */
    public function recordSalePayments(OrdenPuntoVenta $orden, array $payments): bool
    {
        $cashAmount = 0.0;

        foreach ($payments as $payment) {
            if ($payment->isCashMethod) {
                $cashAmount += $payment->pagoNeto();
            }

            $payment->idoperacion = $orden->idoperacion;
            $payment->idsesion = $orden->idsesion;

            if (false === $payment->save()) {
                Tools::log('POS')->error('payment-save-error', [
                    'payment_method' => $payment->codpago
                ]);
                return false;
            }
        }

        // Update expected balance
        return $this->addCash($cashAmount, 'sale');
    }

    /**
     * Records a cash entry (money added to register).
     */
    public function recordCashEntry(float $amount, string $reason = ''): bool
    {
        if ($amount <= 0) {
            Tools::log('POS')->warning('invalid-cash-entry-amount');
            return false;
        }

        return $this->addCash($amount, $reason ?: 'cash-entry');
    }

    /**
     * Records a cash withdrawal (money removed from register).
     */
    public function recordCashWithdrawal(float $amount, string $reason = ''): bool
    {
        if ($amount <= 0) {
            Tools::log('POS')->warning('invalid-cash-withdrawal-amount');
            return false;
        }

        return $this->subtractCash($amount, $reason ?: 'cash-withdrawal');
    }

    /**
     * Records a cash movement (entry or withdrawal) and persists to MovimientoPuntoVenta.
     * This replaces the old StorageService::saveCashMovment method.
     *
     * @param float $amount Positive for entry, negative for withdrawal
     * @param string $description Movement description
     * @return bool
     */
    public function recordCashMovement(float $amount, string $description): bool
    {
        $movimiento = new \FacturaScripts\Dinamic\Model\MovimientoPuntoVenta();

        $movimiento->idsesion = $this->session->idsesion;
        $movimiento->nickusuario = $this->session->nickusuario;
        $movimiento->descripcion = $description;
        $movimiento->total = $amount;

        if (!$movimiento->save()) {
            Tools::log('POS')->error('cash-movement-save-failed', [
                'amount' => $amount,
                'description' => $description
            ]);
            return false;
        }

        // Update expected balance based on amount
        if ($amount > 0) {
            return $this->addCash($amount, $description);
        } elseif ($amount < 0) {
            return $this->subtractCash(abs($amount), $description);
        }

        return true;
    }

    /**
     * Records a refund cash movement and updates expected balance.
     */
    public function recordRefund(float $cashAmount, string $code): bool
    {
        if ($cashAmount >= 0) {
            return true;
        }

        $movement = new \FacturaScripts\Dinamic\Model\MovimientoPuntoVenta();
        $movement->idsesion = $this->session->idsesion;
        $movement->nickusuario = $this->session->nickusuario;
        $movement->descripcion = 'refund: ' . $code;
        $movement->total = $cashAmount;

        if (false === $movement->save()) {
            Tools::log('POS')->error('cash-movement-save-failed', [
                'amount' => $cashAmount,
                'description' => $movement->descripcion
            ]);
            return false;
        }

        return $this->subtractCash(abs($cashAmount), 'refund: ' . $code);
    }

    /**
     * Updates counted balance during session closing.
     */
    public function updateCountedBalance(array $coinsCount): CashBalance
    {
        $totalCounted = $this->calculateTotalFromCoins($coinsCount);

        $this->session->saldocontado = $totalCounted;

        $balance = $this->getBalance();

        if ($balance->hasDiscrepancy()) {
            Tools::log('POS')->warning('cash-balance-discrepancy', [
                'expected' => $balance->getExpected(),
                'counted' => $balance->getCounted(),
                'difference' => $balance->getDifference()
            ]);
        }

        return $balance;
    }

    /**
     * Adds cash to expected balance.
     */
    private function addCash(float $amount, string $reason): bool
    {
        $this->session->saldoesperado += $amount;

        if ($this->session->save()) {
            $this->stateManager->sync($this->session);

            Tools::log('POS')->info('cash-balance-updated', [
                'reason' => $reason,
                'amount' => $amount,
                'new_balance' => $this->session->saldoesperado
            ]);

            return true;
        }

        Tools::log('POS')->error('cash-balance-update-failed');
        return false;
    }

    /**
     * Subtracts cash from expected balance.
     */
    private function subtractCash(float $amount, string $reason): bool
    {
        $this->session->saldoesperado -= $amount;

        if ($this->session->save()) {
            $this->stateManager->sync($this->session);

            Tools::log('POS')->info('cash-balance-updated', [
                'reason' => $reason,
                'amount' => -$amount,
                'new_balance' => $this->session->saldoesperado
            ]);

            return true;
        }

        Tools::log('POS')->error('cash-balance-update-failed');
        return false;
    }

    /**
     * Calculates total cash from coins/bills count.
     *
     * @param array $coinsCount Array with denomination => count
     * @return float Total amount
     */
    private function calculateTotalFromCoins(array $coinsCount): float
    {
        $total = 0.0;

        foreach ($coinsCount as $denomination => $count) {
            $total += (float)$denomination * (int)$count;
        }

        return $total;
    }
}
