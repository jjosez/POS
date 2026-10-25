<?php

/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core;

use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\POS\Contract\PaymentSourceInterface;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceApplication;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceContext;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceContextFactory;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceEntry;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceOption;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceResult;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceSettlement;
use FacturaScripts\Plugins\POS\Lib\Exception\InvalidTransactionException;
use FacturaScripts\Plugins\POS\Lib\Services\Transactions;

/**
 * Central registry for extension-based Payment Sources.
 *
 * Native FormaPago entries are intentionally NOT routed through this manager:
 * they keep the existing terminal configuration, persistence, and validation.
 *
 * Extensions contribute providers by registering them in the
 * `loadPaymentSourceProviders` controller extension hook.
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
        return array_sum(array_map(
            static fn(PaymentSourceEntry $entry): float => $entry->amount,
            $this->sanitise($sources)
        ));
    }

    /**
     * @throws InvalidTransactionException
     */
    public function applyForTransaction(Transactions $transaction, array $sources): PaymentSourceSettlement
    {
        $applied = $this->sanitise($sources);
        if ($applied === []) {
            return new PaymentSourceSettlement();
        }

        $context = $this->contextFactory->createForTransaction(
            $transaction,
            $applied
        );
        $applications = $this->applySources($context, $applied);

        foreach ($applications as $application) {
            if (!$application->result->approved) {
                throw InvalidTransactionException::paymentError(
                    'payment-source-rejected',
                    ['%code%' => $application->entry->code]
                );
            }
        }

        $settlement = new PaymentSourceSettlement($applications);

        Tools::log('POS')->notice(
            'payment-sources-applied',
            [
                '%count%' => (string)count($applications),
                '%amount%' => (string)$settlement->coveredAmount(),
            ]
        );

        return $settlement;
    }

    public function register(PaymentSourceInterface $provider): void
    {
        $code = (string)$provider->getCode();
        if ($code === '' || isset($this->providers[$code])) {
            return;
        }

        $this->providers[$code] = $provider;
    }

    /**
     * Backwards-compatible bulk registration helper.
     *
     * @param iterable<PaymentSourceInterface> $providers
     */
    public function registerExternalProviders(iterable $providers): void
    {
        foreach ($providers as $provider) {
            if ($provider instanceof PaymentSourceInterface) {
                $this->register($provider);
            }
        }
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
                if ($availability->options !== []) {
                    foreach ($availability->options as $option) {
                        if (!$option instanceof PaymentSourceOption) {
                            continue;
                        }

                        $output[] = array_merge($base, [
                            'label' => $option->label,
                            'description' => $option->description ?? $definition->description,
                            'reference' => $option->reference,
                            'metadata' => (object)array_merge($definition->metadata, $option->metadata),
                            'available' => $availability->available,
                            'available_amount' => $option->availableAmount,
                            'status' => $availability->status,
                            'message' => $availability->message,
                        ]);
                    }
                    continue;
                }

                $output[] = array_merge($base, [
                    'reference' => null,
                    'available' => $availability->available,
                    'available_amount' => $availability->isUnlimited() ? null : $availability->availableAmount,
                    'status' => $availability->status,
                    'message' => $availability->message,
                ]);
            } catch (\Throwable $exception) {
                $output[] = array_merge($base, [
                    'reference' => null,
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
     * @param array<int, PaymentSourceEntry|array<string, mixed>> $applied
     * @return PaymentSourceApplication[]
     */
    public function applySources(PaymentSourceContext $context, array $applied): array
    {
        $applications = [];
        foreach ($applied as $rawEntry) {
            $entry = $rawEntry instanceof PaymentSourceEntry
                ? $rawEntry
                : (is_array($rawEntry) ? PaymentSourceEntry::fromArray($rawEntry) : null);
            if ($entry === null) {
                continue;
            }

            $provider = $this->find($entry->code);
            if ($provider === null) {
                $applications[] = new PaymentSourceApplication(
                    entry: $entry,
                    result: PaymentSourceResult::rejected('NOT_AVAILABLE'),
                    label: $entry->code,
                );
                continue;
            }

            $label = $provider->getDefinition()->label;
            try {
                $result = $provider->apply($context, $entry);
                $applications[] = new PaymentSourceApplication($entry, $result, $label);
            } catch (\Throwable $exception) {
                $applications[] = new PaymentSourceApplication(
                    entry: $entry,
                    result: PaymentSourceResult::rejected('ERROR'),
                    label: $label,
                );
            }
        }

        return $applications;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;
    }

    /**
     * @return PaymentSourceEntry[]
     */
    private function sanitise(array $raw): array
    {
        $sources = [];
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $source = PaymentSourceEntry::fromArray($entry);
            if ($source !== null) {
                $sources[] = $source;
            }
        }

        return $sources;
    }
}
