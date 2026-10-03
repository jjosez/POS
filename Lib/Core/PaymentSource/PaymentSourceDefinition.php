<?php

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

final class PaymentSourceDefinition
{
    public function __construct(
        public readonly string $label,
        public readonly ?string $icon = null,
        public readonly ?string $description = null,
        public readonly array $metadata = [],
    ) {
    }
}
