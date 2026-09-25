<?php

namespace FacturaScripts\Plugins\POS\Lib\CustomerAccount;

use FacturaScripts\Core\Tools;
use InvalidArgumentException;

final class AccountResult
{
    public function __construct(
        public readonly AccountStatus $status,
        public readonly float $availableCredit = 0.0,
        public readonly string $message = ''
    ) {
        if (!is_finite($availableCredit) || $availableCredit < 0) {
            throw new InvalidArgumentException(
                'Invalid available credit.'
            );
        }
    }

    public static function approved(
        float $availableCredit = 0.0,
        string $message = ''
    ): self {
        return new self(AccountStatus::APPROVED, $availableCredit, $message);
    }

    public static function rejected(
        AccountStatus $status,
        float $availableCredit = 0.0,
        string $message = ''
    ): self {
        if ($status === AccountStatus::APPROVED) {
            throw new InvalidArgumentException(
                'Use approved() for approved results.'
            );
        }

        return new self(
            $status,
            $availableCredit,
            $message !== ''
                ? $message
                : Tools::lang()->trans($status->translationKey())
        );
    }

    public static function notAvailable(): self
    {
        return self::rejected(AccountStatus::NOT_AVAILABLE);
    }

    public static function notEnabled(): self
    {
        return self::rejected(AccountStatus::NOT_ENABLED);
    }

    public function isApproved(): bool
    {
        return $this->status === AccountStatus::APPROVED;
    }

    public function toArray(): array
    {
        return [
            'customer_account' => $this->isApproved(),
            'available_credit' => $this->availableCredit,
            'status' => $this->status->value,
            'message' => $this->message,
        ];
    }
}
