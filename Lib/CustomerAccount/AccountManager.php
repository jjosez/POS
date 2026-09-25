<?php

namespace FacturaScripts\Plugins\POS\Lib\CustomerAccount;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Core\Tools;

class AccountManager
{
    use ExtensionsTrait;

    private ?AccountProviderInterface $provider = null;

    public function __construct()
    {
        $this->createCustomerAccountManager();
    }

    private function createCustomerAccountManager(): void
    {
        $provider = $this->pipe('customerAccountProvider');

        if ($provider !== null && !$provider instanceof AccountProviderInterface) {
            throw new \LogicException(
                'Invalid customer account provider.'
            );
        }

        $this->provider = $provider;
    }

    public function hasProvider(): bool
    {
        return $this->provider !== null;
    }

    public function check(
        SalesDocument $document,
        string $customerCode,
        float $requestedAmount
    ): AccountResult {
        if (!is_finite($requestedAmount) || $requestedAmount < 0) {
            return AccountResult::rejected(AccountStatus::ERROR);
        }

        if ($requestedAmount === 0.0) {
            return AccountResult::approved();
        }

        if ($this->provider === null) {
            return AccountResult::rejected(AccountStatus::NOT_AVAILABLE);
        }

        try {
            return $this->provider->check(
                $document,
                $customerCode,
                $requestedAmount
            );
        } catch (\Throwable $exception) {
            Tools::log('POS-debug')->error(
                'customer-account-check-error',
                ['%error%' => $exception->getMessage()]
            );

            return AccountResult::rejected(AccountStatus::ERROR);
        }
    }

    public function confirm(SalesDocument $document, string $customerCode, float $requestedAmount): AccountResult
    {
        if ($requestedAmount <= 0) {
            return AccountResult::approved();
        }
        if ($this->provider === null) {
            return AccountResult::rejected(AccountStatus::NOT_AVAILABLE);
        }

        return $this->provider->confirm($document, $customerCode, $requestedAmount);
    }
}
