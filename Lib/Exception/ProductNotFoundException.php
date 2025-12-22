<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Exception;

/**
 * Exception thrown when a product cannot be found.
 */
class ProductNotFoundException extends POSException
{
    public static function withReference(string $reference): self
    {
        return new self(
            'product-not-found',
            ['reference' => $reference]
        );
    }

    public static function withBarcode(string $barcode): self
    {
        return new self(
            'product-not-found-by-barcode',
            ['barcode' => $barcode]
        );
    }
}
