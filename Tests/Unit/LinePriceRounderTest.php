<?php

namespace FacturaScripts\Plugins\POS\Tests\Unit;

use FacturaScripts\Plugins\POS\Lib\Services\LinePriceRounder;
use PHPUnit\Framework\TestCase;

final class LinePriceRounderTest extends TestCase
{
    public function testLeavesPricesWithConfiguredPrecisionUntouched(): void
    {
        $this->assertSame(294.12, LinePriceRounder::roundGrossPrice(294.12, 'nearest', 2));
        $this->assertSame(295.0, LinePriceRounder::roundGrossPrice(295.0, 'up', 2));
    }

    public function testSupportsEveryRoundingMode(): void
    {
        $this->assertSame(294.123, LinePriceRounder::roundGrossPrice(294.123, 'none', 2));
        $this->assertSame(294.12, LinePriceRounder::roundGrossPrice(294.123, 'nearest', 2));
        $this->assertSame(294.13, LinePriceRounder::roundGrossPrice(294.123, 'up', 2));
        $this->assertSame(294.123, LinePriceRounder::roundGrossPrice(294.123, 'precision', 2));
        $this->assertSame(295.0, LinePriceRounder::roundGrossPrice(294.999994, 'precision', 2));
    }

    public function testAdjustsNetPriceFromRoundedTaxInclusivePrice(): void
    {
        $netPrice = LinePriceRounder::adjustNetPrice(254.31034, 16, 'precision', 2);

        $this->assertEqualsWithDelta(295 / 1.16, $netPrice, 0.0000000001);
    }
}
