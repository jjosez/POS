<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Exception;

/**
 * Exception thrown when a transaction is invalid.
 */
class InvalidTransactionException extends POSException
{
    public static function invalidDocumentType(string $type): self
    {
        return new self(
            'invalid-document-type',
            ['type' => $type]
        );
    }

    public static function emptyLines(): self
    {
        return new self('transaction-requires-lines');
    }

    public static function invalidPaymentAmount(float $expected, float $received): self
    {
        return new self(
            'invalid-payment-amount',
            ['expected' => $expected, 'received' => $received]
        );
    }

    public static function saveError(string $reason = ''): self
    {
        return new self(
            'transaction-save-error',
            ['reason' => $reason]
        );
    }
}
