<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Model\Base\SalesDocument;

/**
 * Contract for external customer account/credit providers.
 *
 * A single active provider is resolved by CustomerAccountManager. Providers
 * must guard the check and any credit consumption/reservation against
 * concurrent operations (e.g. a second POS register consuming the same credit).
 */
interface CustomerAccountProviderInterface
{
    public function check(
        SalesDocument $document,
        string $customerCode,
        float $requestedAmount
    ): CustomerAccountResult;

    /**
     * Recheck and record the charge atomically in the caller's DB transaction.
     * Implementations must lock credit, use the document identity as an
     * idempotency key and preserve the originating charge on conversions.
     * Returning approval means the charge has been persisted, not just quoted.
     * All writes must roll back with the POS transaction.
     */
    public function confirm(
        SalesDocument $document,
        string $customerCode,
        float $requestedAmount
    ): CustomerAccountResult;
}
