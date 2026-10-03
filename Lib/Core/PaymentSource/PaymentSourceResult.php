<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

final class PaymentSourceResult
{
    public function __construct(
        public readonly bool $approved = true,
        public readonly string $status = 'APPROVED',
        public readonly ?string $message = null,
        public readonly ?float $consumed = null
    ) {
    }

    public static function approved(?float $consumed = null, ?string $message = null): self
    {
        return new self(true, 'APPROVED', $message, $consumed);
    }

    public static function rejected(string $status, ?string $message = null): self
    {
        return new self(false, $status, $message);
    }

    public function toArray(): array
    {
        return [
            'approved' => $this->approved,
            'status' => $this->status,
            'message' => $this->message,
            'consumed' => $this->consumed,
        ];
    }
}
