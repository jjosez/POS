<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Exception;

use Exception;

/**
 * Base exception for POS domain errors.
 * All POS-specific exceptions should extend this class.
 */
class POSException extends Exception
{
    /**
     * Creates a new POS exception with optional context data.
     *
     * @param string $message Error message
     * @param array $context Additional context for debugging
     * @param int $code Error code
     */
    public function __construct(string $message = "", array $context = [], int $code = 0)
    {
        $fullMessage = $message;

        if (!empty($context)) {
            $fullMessage .= ' | Context: ' . json_encode($context);
        }

        parent::__construct($fullMessage, $code);
    }
}
