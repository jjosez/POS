<?php

namespace FacturaScripts\Plugins\POS\Lib\CustomerAccount;

use FacturaScripts\Core\Model\Base\SalesDocument;

interface AccountProviderInterface
{
    public function check(
        SalesDocument $document,
        string $customerCode,
        float $requestedAmount
    ): AccountResult;

    public function confirm(
        SalesDocument $document,
        string $customerCode,
        float $requestedAmount
    ): AccountResult;

}
