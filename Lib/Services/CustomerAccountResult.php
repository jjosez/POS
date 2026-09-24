<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Tools;

/**
 * Immutable result of a customer account (credit) check.
 * Keys are kept in snake_case so the frontend can consume the same contract.
 */
final class CustomerAccountResult
{
    public const STATUS_APPROVED = 'approved';
    public const STATUS_NOT_ENABLED = 'not_enabled';
    public const STATUS_INSUFFICIENT_CREDIT = 'insufficient_credit';
    public const STATUS_NOT_AVAILABLE = 'not_available';
    public const STATUS_ERROR = 'error';

    public function __construct(
        public readonly bool $customerAccount,
        public readonly float $availableCredit,
        public readonly string $status,
        public readonly string $message = ''
    ) {
        if (!in_array($status, [self::STATUS_APPROVED, self::STATUS_NOT_ENABLED,
            self::STATUS_INSUFFICIENT_CREDIT, self::STATUS_NOT_AVAILABLE, self::STATUS_ERROR], true)
            || !is_finite($availableCredit) || $availableCredit < 0
            || $customerAccount !== ($status === self::STATUS_APPROVED)) {
            throw new \InvalidArgumentException('Invalid customer account result.');
        }
    }

    public static function approved(float $availableCredit = 0.0, string $message = ''): self
    {
        return new self(true, $availableCredit, self::STATUS_APPROVED, $message);
    }

    public static function notEnabled(float $availableCredit = 0.0, string $message = ''): self
    {
        return new self(
            false,
            $availableCredit,
            self::STATUS_NOT_ENABLED,
            $message ?: Tools::lang()->trans('customer-account-not-enabled')
        );
    }

    public static function insufficientCredit(float $availableCredit = 0.0, string $message = ''): self
    {
        return new self(
            false,
            $availableCredit,
            self::STATUS_INSUFFICIENT_CREDIT,
            $message ?: Tools::lang()->trans('customer-account-insufficient-credit')
        );
    }

    public static function notAvailable(string $message = ''): self
    {
        return new self(
            false,
            0.0,
            self::STATUS_NOT_AVAILABLE,
            $message ?: Tools::lang()->trans('customer-account-not-available')
        );
    }

    public static function error(string $message = ''): self
    {
        return new self(
            false,
            0.0,
            self::STATUS_ERROR,
            $message ?: Tools::lang()->trans('customer-account-error')
        );
    }

    public function isApproved(): bool
    {
        return $this->customerAccount && $this->status === self::STATUS_APPROVED
            && is_finite($this->availableCredit) && $this->availableCredit >= 0;
    }

    public function toArray(): array
    {
        return [
            'customer_account' => $this->isApproved(),
            'available_credit' => $this->availableCredit,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }
}
