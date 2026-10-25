<?php

declare(strict_types=1);

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

/**
 * Result of applying one concrete Payment Source entry.
 */
final readonly class PaymentSourceApplication
{
    public function __construct(
        public PaymentSourceEntry $entry,
        public PaymentSourceResult $result,
        public string $label,
    ) {
    }

    public function consumedAmount(): float
    {
        return (float)($this->result->consumed ?? 0.0);
    }
}
