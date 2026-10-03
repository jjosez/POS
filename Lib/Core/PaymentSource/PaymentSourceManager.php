<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

use FacturaScripts\Plugins\POS\Contract\PaymentSourceProviderInterface;

/**
 * Central registry for extension-based Payment Sources.
 *
 * Native FormaPago entries are intentionally NOT routed through this manager:
 * they keep the existing terminal configuration, persistence and validation.
 *
 * Extensions contribute providers by invoking
 * `HookManager::addPaymentSourceProvider()` from inside their
 * `loadPaymentSourceRegistration` extension hook.
 */
class PaymentSourceManager
{
    /** @var array<string, PaymentSourceProviderInterface> */
    private array $providers = [];
    private bool $booted = false;

    public function register(PaymentSourceProviderInterface $provider): void
    {
        $code = (string)$provider->getCode();
        if ($code === '' || isset($this->providers[$code])) {
            return;
        }

        $this->providers[$code] = $provider;
    }

    public function isRegistered(string $code): bool
    {
        $this->boot();

        return isset($this->providers[$code]);
    }

    /**
     * @return PaymentSourceProviderInterface[]
     */
    public function getProviders(): array
    {
        $this->boot();

        return array_values($this->providers);
    }

    public function find(string $code): ?PaymentSourceProviderInterface
    {
        $this->boot();

        return $this->providers[$code] ?? null;
    }

    /**
     * Lightweight definition catalog used by the POS bootstrap. Only static
     * metadata is returned; availability is queried on demand.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDefinitions(): array
    {
        $definitions = [];
        foreach ($this->getProviders() as $provider) {
            $definition = $provider->getDefinition();
            $definitions[] = [
                'code' => $provider->getCode(),
                'label' => (string)($definition['label'] ?? $provider->getCode()),
                'icon' => $definition['icon'] ?? null,
                'description' => $definition['description'] ?? null,
                'metadata' => (object)($definition['metadata'] ?? []),
            ];
        }

        return $definitions;
    }

    /**
     * Returns true when at least one provider is registered.
     */
    public function hasProviders(): bool
    {
        $this->boot();

        return !empty($this->providers);
    }

    /**
     * Query availability and metadata for every registered provider within the
     * current checkout context.
     *
     * @return array<int, array<string, mixed>>
     */
    public function checkAvailability(PaymentSourceContext $context): array
    {
        $output = [];
        foreach ($this->getProviders() as $provider) {
            $definition = $provider->getDefinition();
            $base = [
                'code' => $provider->getCode(),
                'label' => (string)($definition['label'] ?? $provider->getCode()),
                'icon' => $definition['icon'] ?? null,
                'description' => $definition['description'] ?? null,
                'metadata' => (object)($definition['metadata'] ?? []),
            ];

            try {
                $availability = $provider->checkAvailability($context);
                $output[] = array_merge($base, [
                    'available' => $availability->available,
                    'available_amount' => $availability->isUnlimited() ? null : $availability->availableAmount,
                    'status' => $availability->status,
                    'message' => $availability->message,
                ]);
            } catch (\Throwable $exception) {
                $output[] = array_merge($base, [
                    'available' => false,
                    'available_amount' => 0.0,
                    'status' => 'ERROR',
                    'message' => null,
                ]);
            }
        }

        return $output;
    }

    /**
     * Apply the requested amount to each provider in `applied`. Each provider
     * is responsible for its own domain side effects; the manager only routes
     * the request and normalises the resulting status payload.
     *
     * @param array<int, array<string, mixed>> $applied
     * @return array<int, array<string, mixed>>
     */
    public function applySources(PaymentSourceContext $context, array $applied): array
    {
        $results = [];
        foreach ($applied as $entry) {
            $code = (string)($entry['code'] ?? '');
            $amount = (float)($entry['amount'] ?? 0.0);

            if ($code === '' || $amount <= 0) {
                continue;
            }

            $provider = $this->find($code);
            if ($provider === null) {
                $results[] = [
                    'code' => $code,
                    'approved' => false,
                    'status' => 'NOT_AVAILABLE',
                    'message' => null,
                    'consumed' => null,
                    'metadata' => new \stdClass(),
                ];
                continue;
            }

            try {
                $result = $provider->apply($context, $amount);
                $payload = $result->toArray();
                $payload['code'] = $code;
                $results[] = $payload;
            } catch (\Throwable $exception) {
                $results[] = [
                    'code' => $code,
                    'approved' => false,
                    'status' => 'ERROR',
                    'message' => null,
                    'consumed' => null,
                    'metadata' => new \stdClass(),
                ];
            }
        }

        return $results;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;
    }

    /**
     * Push externally registered providers (typically contributed by the
     * `PaymentSourceRegistration` hook) into the manager.
     *
     * @param iterable<PaymentSourceProviderInterface> $providers
     */
    public function registerExternalProviders(iterable $providers): void
    {
        $this->boot();

        foreach ($providers as $provider) {
            if ($provider instanceof PaymentSourceProviderInterface) {
                $this->register($provider);
            }
        }
    }
}
