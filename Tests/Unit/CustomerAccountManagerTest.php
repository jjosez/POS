<?php

namespace FacturaScripts\Plugins\POS\Tests\Unit;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Plugins\POS\Lib\Services\CustomerAccountManager;
use FacturaScripts\Plugins\POS\Lib\Services\CustomerAccountProviderInterface;
use FacturaScripts\Plugins\POS\Lib\Services\CustomerAccountResult;
use PHPUnit\Framework\TestCase;

final class CustomerAccountManagerTest extends TestCase
{
    public function testRejectsContradictoryAuthorization(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CustomerAccountResult(true, 1000, CustomerAccountResult::STATUS_NOT_ENABLED);
    }

    protected function tearDown(): void
    {
        $property = new \ReflectionProperty(CustomerAccountManager::class, 'provider');
        $property->setValue(null, null);
    }

    public function testQuoteDoesNotReplaceFinalConfirmation(): void
    {
        if (!defined('FS_FOLDER')) {
            define('FS_FOLDER', dirname(__DIR__, 4));
        }
        $document = $this->getMockBuilder(SalesDocument::class)->disableOriginalConstructor()->getMockForAbstractClass();
        $manager = new CustomerAccountManager();
        self::assertFalse($manager->check($document, 'CLIENT', 800)->isApproved());
        self::assertFalse($manager->confirm($document, 'CLIENT', 800)->isApproved());
        self::assertTrue($manager->confirm($document, 'CLIENT', 0)->isApproved());

        $provider = $this->createMock(CustomerAccountProviderInterface::class);
        $provider->expects(self::once())->method('check')->with($document, 'CLIENT', 800)
            ->willReturn(CustomerAccountResult::approved(1000));
        $provider->expects(self::once())->method('confirm')->with($document, 'CLIENT', 800)
            ->willReturn(CustomerAccountResult::insufficientCredit(100));
        CustomerAccountManager::registerProvider($provider);
        self::assertTrue($manager->check($document, 'CLIENT', 800)->isApproved());
        self::assertFalse($manager->confirm($document, 'CLIENT', 800)->isApproved());
        $this->expectException(\LogicException::class);
        CustomerAccountManager::registerProvider($this->createMock(CustomerAccountProviderInterface::class));
    }
}
