<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

enum PaymentPolicy: string
{
    case REQUIRED = 'required';
    case CUSTOMER_ACCOUNT = 'customer-account';
}
