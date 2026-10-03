<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Plugins\POS\Contract\PaymentSourceProviderInterface;
use FacturaScripts\Plugins\POS\Lib\Services\Configuration;
use FacturaScripts\Plugins\POS\Model\FormaPagoPuntoVenta;

/**
 * Registry of payment source providers keyed by opaque code.
 *
 * Built-in providers come from the FormaPago entries configured on the terminal.
 * Extensions register additional providers via the standard
 * ExtensionsTrait pipe ('paymentSourceProvider').
 */
class PaymentSourceManager
{
    use ExtensionsTrait;

    /** @var array<string, PaymentSourceProviderInterface> */
    private array $providers = [];
    private Configuration $config;
    private bool $booted = false;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    public function register(PaymentSourceProviderInterface $provider): void
    {
        $code = $provider->getCode();
        if (isset($this->providers[$code])) {
            // win by registered order: keep existing, ignore duplicate
            return;
        }
        $this->providers[$code] = $provider;
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
     * Returns a normalized list of definitions as the POS expects them.
     *
     * Shape per entry:
     *   { code, label, icon, metadata: object }
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDefinitions(): array
    {
        $definitions = [];
        foreach ($this->getProviders() as $provider) {
            $definitions[] = $provider->getDefinition();
        }
        return $definitions;
    }

    /**
     * Returns definitions contributed by built-in providers only.
     *
     * Used to seed the JS catalog at boot with the FormaPago configured
     * on the terminal. Extension sources come in later through the
     * `payment-sources:availability` endpoint and are never part of the
     * static catalog.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBuiltInDefinitions(): array
    {
        $definitions = [];
        foreach ($this->getProviders() as $provider) {
            if (!$provider instanceof PaymentMethodProvider) {
                continue;
            }
            $definitions[] = $provider->getDefinition();
        }
        return $definitions;
    }

    /**
     * Returns per-provider availability for the current sale context.
     *
     * Shape per entry:
     *   { code, label, icon, available, available_amount, status, message, metadata }
     *
     * @return array<int, array<string, mixed>>
     */
    public function checkAvailability(PaymentSourceContext $context): array
    {
        $output = [];
        foreach ($this->getProviders() as $provider) {
            $definition = $provider->getDefinition();
            try {
                $availability = $provider->checkAvailability($context);
                $output[] = [
                    'code' => $provider->getCode(),
                    'label' => $definition['label'] ?? $provider->getCode(),
                    'icon' => $definition['icon'] ?? null,
                    'metadata' => $definition['metadata'] ?? new \stdClass(),
                    'available' => $availability->available,
                    'available_amount' => $availability->isUnlimited() ? null : $availability->availableAmount,
                    'status' => $availability->status,
                    'message' => $availability->message,
                ];
            } catch (\Throwable $exception) {
                $output[] = [
                    'code' => $provider->getCode(),
                    'label' => $definition['label'] ?? $provider->getCode(),
                    'icon' => $definition['icon'] ?? null,
                    'metadata' => $definition['metadata'] ?? new \stdClass(),
                    'available' => false,
                    'available_amount' => 0.0,
                    'status' => 'ERROR',
                    'message' => $exception->getMessage(),
                ];
            }
        }
        return $output;
    }

    /**
     * Authorize every applied non-payment-method source. The caller decides
     * which entries are payment-method (via metadata.paymentMethod === true)
     * and which go through this apply path.
     *
     * @param array<int, array{code: string, amount: float}> $applied
     * @return array<int, array<string, mixed>>
     */
    public function applySources(PaymentSourceContext $context, array $applied): array
    {
        $results = [];
        foreach ($applied as $entry) {
            $code = (string)($entry['code'] ?? '');
            $amount = (float)($entry['amount'] ?? 0.0);

            if ($amount <= 0) {
                continue;
            }

            $provider = $this->find($code);
            if ($provider === null) {
                $results[] = [
                    'code' => $code,
                    'approved' => false,
                    'status' => 'NOT_AVAILABLE',
                    'message' => null,
                ];
                continue;
            }

            try {
                $result = $provider->apply($context, $amount);
                $results[] = array_merge(['code' => $code], $result->toArray());
            } catch (\Throwable $exception) {
                $results[] = [
                    'code' => $code,
                    'approved' => false,
                    'status' => 'ERROR',
                    'message' => $exception->getMessage(),
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

        $this->registerConfiguredPaymentMethods();

        foreach ($this->getExtensions() as $extension) {
            if (!$extension instanceof PaymentSourceProviderInterface) {
                continue;
            }
            $this->register($extension);
        }
    }

    private function registerConfiguredPaymentMethods(): void
    {
        foreach ($this->config->getPaymentMethods() as $method) {
            if (!$method instanceof FormaPagoPuntoVenta) {
                continue;
            }
            $this->register(new PaymentMethodProvider($method));
        }
    }

    /**
     * @return PaymentSourceProviderInterface[]
     */
    private function getExtensions(): array
    {
        $candidates = $this->pipe('paymentSourceProvider') ?? [];
        if (!is_array($candidates)) {
            return [];
        }

        return $candidates;
    }
}
