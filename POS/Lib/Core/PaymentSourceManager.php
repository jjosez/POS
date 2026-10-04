<?php

/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core;

use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\POS\Contract\PaymentSourceInterface;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceContext;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceContextFactory;
use FacturaScripts\Plugins\POS\Lib\Exception\InvalidTransactionException;
use FacturaScripts\Plugins\POS\Lib\Services\Transactions;

/**
 * Central registry for extension-based Payment Sources.
 *
 * Native FormaPago entries are intentionally NOT routed through this manager:
 * they keep the existing terminal configuration, persistence, and validation.
 *
 * Extensions contribute to providers by invoking
 * `HookManager::addPaymentSourceProvider()` from inside their
 * `loadPaymentSourceProviders` extension hook.
 */
class PaymentSourceManager
{
    /** @var array<string, PaymentSourceInterface> */
    private array $providers = [];
    private bool $booted = false;

    public function __construct(private readonly PaymentSourceContextFactory $contextFactory)
    {
    }

    public function createDiscoveryContext(array $payload): PaymentSourceContext
    {
        return $this->contextFactory->createDiscovery($payload);
    }

    public function requestedAmount(array $sources): float
    {
        return array_sum(array_column($this->sanitise($sources), 'amount'));
    }

    /**
     * @throws InvalidTransactionException
     */
    public function applyForTransaction(Transactions $transaction, array $sources): float
    {
        $applied = $this->sanitise($sources);
        if ($applied === []) {
            return 0.0;
        }

        $context = $this->contextFactory->createForTransaction($transaction, $applied);
        $results = $this->applySources($context, $applied);
        $covered = 0.0;

        foreach ($results as $result) {
            if (!($result['approved'] ?? false)) {
                throw InvalidTransactionException::paymentError(
                    'payment-source-rejected',
                    ['%code%' => (string)($result['code'] ?? '')]
                );
            }
            $covered += (float)($result['consumed'] ?? 0.0);
        }

        Tools::log('POS')->notice(
            'payment-sources-applied',
            ['%count%' => (string)count($results), '%amount%' => (string)$covered]
        );

        return $covered;
    }

    public function register(PaymentSourceInterface $provider): void
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
     * @return PaymentSourceInterface[]
     */
    public function getProviders(): array
    {
        $this->boot();

        return array_values($this->providers);
    }

    public function find(string $code): ?PaymentSourceInterface
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
                'code' => $definition->code,
                'label' => $definition->label,
                'icon' => $definition->icon,
                'description' => $definition->description,
                'metadata' => (object)$definition->metadata,
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
                'code' => $definition->code,
                'label' => $definition->label,
                'icon' => $definition->icon,
                'description' => $definition->description,
                'metadata' => (object)$definition->metadata,
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
     * the request and normalizes the resulting status payload.
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
     * @return array<int, array{code: string, amount: float}>
     */
    private function sanitise(array $raw): array
    {
        $sources = [];
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $code = (string)($entry['code'] ?? '');
            $amount = (float)($entry['amount'] ?? 0.0);
            if ($code !== '' && $amount > 0.0) {
                $sources[] = ['code' => $code, 'amount' => $amount];
            }
        }

        return $sources;
    }
}
