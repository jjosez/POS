<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

use FacturaScripts\Plugins\POS\Contract\PaymentSourceProviderInterface;
use FacturaScripts\Plugins\POS\Model\FormaPagoPuntoVenta;

/**
 * Native FormaPago source.
 *
 * One provider instance per configured FormaPagoPuntoVenta entry. The provider's
 * code is the FormaPago codpago, so resolution is by code only — the POS never
 * has to know which card is a "payment method" except through metadata.
 */
final class PaymentMethodProvider implements PaymentSourceProviderInterface
{
    private FormaPagoPuntoVenta $method;

    public function __construct(FormaPagoPuntoVenta $method)
    {
        $this->method = $method;
    }

    public function getCode(): string
    {
        return (string)$this->method->codpago;
    }

    public function getDefinition(): array
    {
        return [
            'code' => (string)$this->method->codpago,
            'label' => (string)$this->method->descripcion(),
            'icon' => $this->method->iconClass(),
            'metadata' => [
                'paymentMethod' => true,
                'changeAllowed' => (bool)$this->method->recibecambio,
            ],
        ];
    }

    public function checkAvailability(PaymentSourceContext $context): PaymentSourceAvailability
    {
        return PaymentSourceAvailability::unlimited();
    }

    public function apply(PaymentSourceContext $context, float $amount): PaymentSourceResult
    {
        return PaymentSourceResult::approved($amount);
    }
}
