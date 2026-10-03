<?php

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

enum PaymentPolicy: string
{
    case REQUIRED = 'required';
    case OPTIONAL = 'optional';
}
