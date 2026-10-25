<?php

declare(strict_types=1);

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

/**
 * Aggregate result for all Payment Sources applied to a transaction.
 */
final readonly class PaymentSourceSettlement
{
    /**
     * @param PaymentSourceApplication[] $applications
     */
    public function __construct(public array $applications = [])
    {
    }

    public function coveredAmount(): float
    {
        return array_sum(array_map(
            static fn(PaymentSourceApplication $application): float => $application->consumedAmount(),
            $this->applications
        ));
    }

    public function isEmpty(): bool
    {
        return $this->applications === [];
    }
}
