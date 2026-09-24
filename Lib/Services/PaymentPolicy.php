<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

enum PaymentPolicy: string
{
    case REQUIRED = 'required';
    case OPTIONAL = 'optional';
    case CUSTOMER_ACCOUNT = 'customer-account';
}
