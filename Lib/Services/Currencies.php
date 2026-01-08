<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Model\Divisa;
use FacturaScripts\Core\Tools;

class Currencies
{
    /**
     * @var Divisa
     */
    protected Divisa $currency;

    public function __construct()
    {
        $this->currency = new Divisa();
    }

    /**
     * @return Divisa
     */
    public function getCurrency(?string $code = null): Divisa
    {
        if (is_null($code)) {
            $code = Tools::settings('default', 'coddivisa');
        }

        if ($this->currency->coddivisa === $code) {
            return $this->currency;
        }

        $this->currency = Divisas::get($code);
        return $this->currency;
    }

    public function getSymbol(): string
    {
        return $this->currency->simbolo;
    }

    public function getDecimals(): int
    {
        return Tools::settings('default', 'decimals', 2);
    }

    public function getSeparator(): string
    {
        return Tools::settings('default', 'decimal_separator', ',');
    }

    public function getCurrenciesList(): array
    {
        return Divisas::all();
    }
}
