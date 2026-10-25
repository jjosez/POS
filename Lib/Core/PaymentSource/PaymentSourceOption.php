<?php

declare(strict_types=1);

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

/**
 * One concrete resource offered by a Payment Source during discovery.
 */
final readonly class PaymentSourceOption
{
    public function __construct(
        public string $reference,
        public string $label,
        public float $availableAmount,
        public ?string $description = null,
        public array $metadata = [],
    ) {
        if (trim($reference) === '') {
            throw new \InvalidArgumentException('Payment Source option reference cannot be empty.');
        }
        if (trim($label) === '') {
            throw new \InvalidArgumentException('Payment Source option label cannot be empty.');
        }
        if (!is_finite($availableAmount) || $availableAmount <= 0.0) {
            throw new \InvalidArgumentException('Payment Source option amount must be finite and greater than zero.');
        }
    }
}
