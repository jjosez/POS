<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Exception;

/**
 * Exception thrown when POS configuration is invalid or incomplete.
 */
class POSConfigurationException extends POSException
{
    public static function noPaymentMethods(): self
    {
        return new self('no-payment-method-set');
    }

    public static function noCashPaymentMethod(): self
    {
        return new self('no-cash-payment-method-set');
    }

    public static function noDefaultDocument(): self
    {
        return new self('no-default-document-set');
    }

    public static function noDenominations(): self
    {
        return new self('no-currency-denominations');
    }

    public static function invalidSettings(array $missingFields): self
    {
        return new self(
            'invalid-pos-settings',
            ['missing_fields' => $missingFields]
        );
    }
}
