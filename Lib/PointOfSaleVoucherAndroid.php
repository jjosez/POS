<?php

namespace FacturaScripts\Plugins\POS\Lib;

class PointOfSaleVoucherAndroid extends PointOfSaleVoucher
{
    /**
     * Base64 encoded string for Rawbt App
     *
     * @return string
     */
    public function getResult(): string
    {
        $buffer = parent::getResult();

        return "intent:base64," . base64_encode($buffer) . "#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;";
    }

    protected function buildHeader(): void
    {
        $this->printLogo();
        parent::buildHeader();
    }
}
