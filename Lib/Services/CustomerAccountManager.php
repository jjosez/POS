<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Core\Tools;

class CustomerAccountManager
{
    use ExtensionsTrait;

    private ?CustomerAccountProviderInterface $provider = null;

    public function __construct()
    {
        $this->createCustomerAccountManager();
    }

    private function createCustomerAccountManager(): void
    {
        $provider = $this->pipe('customerAccountProvider');

        if (
            $provider !== null
            && !$provider instanceof CustomerAccountProviderInterface
        ) {
            throw new \LogicException('Invalid customer account provider.');
        }

        $this->provider = $provider;
    }

    public function hasProvider(): bool
    {
        return $this->provider !== null;
    }

    public function check(SalesDocument $document, string $customerCode, float $requestedAmount): CustomerAccountResult
    {
        if ($requestedAmount <= 0) {
            return CustomerAccountResult::approved();
        }

        if (null === $this->provider) {
            return CustomerAccountResult::notAvailable();
        }

        try {
            return $this->provider->check($document, $customerCode, (float)$requestedAmount);
        } catch (\Throwable $exception) {
            Tools::log('POS-debug')->error('customer-account-check-error', [
                '%error%' => $exception->getMessage(),
            ]);

            return CustomerAccountResult::error();
        }
    }

    public function confirm(SalesDocument $document, string $customerCode, float $requestedAmount): CustomerAccountResult
    {
        if ($requestedAmount <= 0) {
            return CustomerAccountResult::approved();
        }
        if ($this->provider === null) {
            return CustomerAccountResult::notAvailable();
        }

        // Let exceptions propagate so the controller rolls back all writes.
        return $this->provider->confirm($document, $customerCode, $requestedAmount);
    }
}
