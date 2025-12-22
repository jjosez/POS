<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\Session;

/**
 * Value Object representing POS session status.
 * Immutable object ensuring valid session states.
 */
final class SessionStatus
{
    private const STATUS_OPEN = 'open';
    private const STATUS_CLOSED = 'closed';

    private string $status;

    private function __construct(string $status)
    {
        $this->status = $status;
    }

    public static function open(): self
    {
        return new self(self::STATUS_OPEN);
    }

    public static function closed(): self
    {
        return new self(self::STATUS_CLOSED);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function toString(): string
    {
        return $this->status;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
