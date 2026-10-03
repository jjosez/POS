<?php

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

final class PaymentSourceDefinition
{
    public function __construct(
        public readonly string $code,
        public readonly string $label,
        public readonly string $description,
        public readonly string $icon,
        public readonly array $metadata
    ) {
    }
}
