<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

use FacturaScripts\Core\Model\Base\SalesDocument;

final class PaymentSourceContext
{
    /**
     * @param array<int, array<string, mixed>> $sources
     */
    public function __construct(
        public readonly SalesDocument $document,
        public readonly ?string $customerCode = null,
        public readonly float $total = 0.0,
        public readonly float $coveredAmount = 0.0,
        public readonly array $sources = []
    ) {
    }

    public function remainingAmount(): float
    {
        return max(0.0, $this->total - $this->coveredAmount);
    }
}
