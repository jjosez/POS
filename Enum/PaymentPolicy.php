<?php

namespace FacturaScripts\Plugins\POS\Enum;

enum PaymentPolicy: string
{
    case REQUIRED = 'required';
    case OPTIONAL = 'optional';
}
