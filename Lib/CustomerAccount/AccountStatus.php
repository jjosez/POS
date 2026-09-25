<?php

namespace FacturaScripts\Plugins\POS\Lib\CustomerAccount;

enum AccountStatus: string
{
    case APPROVED = 'approved';
    case NOT_ENABLED = 'not-enabled';
    case INSUFFICIENT_CREDIT = 'insufficient-credit';
    case NOT_AVAILABLE = 'not-available';
    case ERROR = 'error';

    public function translationKey(): string
    {
        return 'customer-account-' . $this->value;
    }
}
