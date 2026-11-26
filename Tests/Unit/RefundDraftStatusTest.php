<?php

namespace FacturaScripts\Plugins\POS\Tests\Unit;

use FacturaScripts\Plugins\POS\Model\DevolucionPuntoVenta;
use FacturaScripts\Plugins\POS\Tests\Fixtures\RefundDraftFake;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RefundDraftStatusTest extends TestCase
{
    public function testCompletingDraftKeepsRecordAndSetsAuditFields(): void
    {
        $reflection = new ReflectionClass(RefundDraftFake::class);
        /** @var RefundDraftFake $draft */
        $draft = $reflection->newInstanceWithoutConstructor();
        $draft->estado = DevolucionPuntoVenta::STATUS_OPEN;

        self::assertTrue($draft->setAsCompleted());
        self::assertSame(DevolucionPuntoVenta::STATUS_COMPLETED, $draft->estado);
        self::assertNotEmpty($draft->fechacompletado);
        self::assertNotEmpty($draft->horacompletado);
        self::assertSame(1, $draft->saveCalls);
    }
}
