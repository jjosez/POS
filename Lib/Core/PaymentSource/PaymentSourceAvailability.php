<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

final class PaymentSourceAvailability
{
    /**
     * @param PaymentSourceOption[] $options
     */
    public function __construct(
        public readonly bool $available = true,
        public readonly ?float $availableAmount = null,
        public readonly string $status = 'AVAILABLE',
        public readonly ?string $message = null,
        public readonly array $options = [],
    ) {
        if ($availableAmount !== null && (!is_finite($availableAmount) || $availableAmount < 0)) {
            throw new \InvalidArgumentException('Invalid available amount.');
        }
        foreach ($options as $option) {
            if (!$option instanceof PaymentSourceOption) {
                throw new \InvalidArgumentException('Invalid Payment Source option.');
            }
        }
    }

    public static function unlimited(): self
    {
        return new self(true, null, 'AVAILABLE', null);
    }

    public static function allowed(float $availableAmount, ?string $message = null): self
    {
        return new self(true, $availableAmount, 'AVAILABLE', $message);
    }

    public static function blocked(string $status, ?string $message = null, ?float $availableAmount = 0.0): self
    {
        return new self(false, $availableAmount ?? 0.0, $status, $message);
    }

    /**
     * @param PaymentSourceOption[] $options
     */
    public static function withOptions(array $options, ?string $message = null): self
    {
        foreach ($options as $option) {
            if (!$option instanceof PaymentSourceOption) {
                throw new \InvalidArgumentException('Invalid Payment Source option.');
            }
        }

        if ($options === []) {
            return self::blocked('NOT_AVAILABLE', $message);
        }

        $availableAmount = array_sum(array_map(
            static fn(PaymentSourceOption $option): float => $option->availableAmount,
            $options
        ));

        return new self(true, $availableAmount, 'AVAILABLE', $message, $options);
    }

    public function isUnlimited(): bool
    {
        return $this->availableAmount === null;
    }

    public function toArray(): array
    {
        return [
            'available' => $this->available,
            'available_amount' => $this->isUnlimited() ? null : $this->availableAmount,
            'status' => $this->status,
            'message' => $this->message,
            'options' => array_map(static fn(PaymentSourceOption $option): array => [
                'reference' => $option->reference,
                'label' => $option->label,
                'available_amount' => $option->availableAmount,
                'description' => $option->description,
                'metadata' => (object)$option->metadata,
            ], $this->options),
        ];
    }
}
