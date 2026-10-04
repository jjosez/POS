<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\Session;

/**
 * Value Object representing cash balance in a POS session.
 * Tracks expected vs actual cash amounts.
 */
final class CashBalance
{
    private float $expected;
    private float $counted;

    public function __construct(float $expected = 0.0, float $counted = 0.0)
    {
        $this->expected = $expected;
        $this->counted = $counted;
    }

    public static function empty(): self
    {
        return new self(0.0, 0.0);
    }

    public static function fromInitialAmount(float $amount): self
    {
        return new self($amount, $amount);
    }

    /**
     * Adds cash to expected balance (sale, cash entry, etc.)
     */
    public function addExpected(float $amount): self
    {
        return new self($this->expected + $amount, $this->counted);
    }

    /**
     * Subtracts cash from expected balance (withdrawal, refund, etc.)
     */
    public function subtractExpected(float $amount): self
    {
        return new self($this->expected - $amount, $this->counted);
    }

    /**
     * Updates counted balance (during closing)
     */
    public function updateCounted(float $amount): self
    {
        return new self($this->expected, $amount);
    }

    public function getExpected(): float
    {
        return $this->expected;
    }

    public function getCounted(): float
    {
        return $this->counted;
    }

    /**
     * Calculates difference between expected and counted
     */
    public function getDifference(): float
    {
        return $this->counted - $this->expected;
    }

    /**
     * Returns true if there's a discrepancy
     */
    public function hasDiscrepancy(): bool
    {
        return abs($this->getDifference()) > 0.01;
    }

    public function toArray(): array
    {
        return [
            'expected' => $this->expected,
            'counted' => $this->counted,
            'difference' => $this->getDifference(),
            'has_discrepancy' => $this->hasDiscrepancy()
        ];
    }
}
