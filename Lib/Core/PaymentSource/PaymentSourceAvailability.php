<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

final class PaymentSourceAvailability
{
    public function __construct(
        public readonly bool $available = true,
        public readonly ?float $availableAmount = null,
        public readonly string $status = 'AVAILABLE',
        public readonly ?string $message = null
    ) {
        if ($availableAmount !== null && (!is_finite($availableAmount) || $availableAmount < 0)) {
            throw new \InvalidArgumentException('Invalid available amount.');
        }
    }

    public static function unlimited(): self
    {
        return new self(true, null, 'AVAILABLE', null);
    }

    public static function allowed(float $availableAmount, ?string $message = null): self
    {
        return new self(true, $availableAmount, 'AVAILABLE', $message);
    }

    public static function blocked(string $status, ?string $message = null, ?float $availableAmount = 0.0): self
    {
        return new self(false, $availableAmount ?? 0.0, $status, $message);
    }

    public function isUnlimited(): bool
    {
        return $this->availableAmount === null;
    }

    public function toArray(): array
    {
        return [
            'available' => $this->available,
            'available_amount' => $this->isUnlimited() ? null : $this->availableAmount,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }
}
