<?php

namespace FacturaScripts\Plugins\POS\Contract;

use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceAvailability;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceContext;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceDefinition;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceResult;

interface PaymentSourceInterface
{
    public function getCode(): string;

    public function getDefinition(): PaymentSourceDefinition;

    public function checkAvailability(PaymentSourceContext $context): PaymentSourceAvailability;

    public function apply(PaymentSourceContext $context, float $amount): PaymentSourceResult;
}
