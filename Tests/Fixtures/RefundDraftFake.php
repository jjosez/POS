<?php

namespace FacturaScripts\Plugins\POS\Tests\Fixtures;

use FacturaScripts\Plugins\POS\Model\DevolucionPuntoVenta;

final class RefundDraftFake extends DevolucionPuntoVenta
{
    public int $saveCalls = 0;

    public function save(): bool
    {
        ++$this->saveCalls;
        return true;
    }
}
