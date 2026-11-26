<?php

namespace FacturaScripts\Plugins\POS\Tests\Unit;

use FacturaScripts\Plugins\POS\Lib\Services\PaymentTracking;
use FacturaScripts\Plugins\POS\Model\PagoPuntoVentaSeguimiento;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PaymentTrackingTest extends TestCase
{
    public function testFullTransformationUsesAllPayments(): void
    {
        self::assertSame(1.0, PaymentTracking::documentRatio(2000.0, 2000.0));
    }

    public function testPartialTransformationUsesProportionalPayments(): void
    {
        self::assertSame(0.5, PaymentTracking::documentRatio(2000.0, 1000.0));
    }

    public function testTransformationNeverAllocatesMoreThanSource(): void
    {
        self::assertSame(1.0, PaymentTracking::documentRatio(1000.0, 1500.0));
    }

    public function testNegativeDocumentsUseAbsoluteRatio(): void
    {
        self::assertSame(0.5, PaymentTracking::documentRatio(-2000.0, -1000.0));
    }

    public function testGroupsAllocationsByPaymentMethod(): void
    {
        $cash1 = $this->tracking('CASH', 1000.0);
        $cash2 = $this->tracking('CASH', 500.0);
        $card = $this->tracking('CARD', 500.0);

        $result = (new PaymentTracking())->groupByPaymentMethod([$cash1, $cash2, $card]);

        self::assertSame([
            'CASH' => 1500.0,
            'CARD' => 500.0,
        ], $result);
    }

    private function tracking(string $method, float $amount): PagoPuntoVentaSeguimiento
    {
        $tracking = (new ReflectionClass(PagoPuntoVentaSeguimiento::class))
            ->newInstanceWithoutConstructor();
        $tracking->codpago = $method;
        $tracking->cantidad = $amount;
        return $tracking;
    }
}
