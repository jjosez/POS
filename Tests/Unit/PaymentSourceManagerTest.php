<?php

namespace FacturaScripts\Plugins\POS\Tests\Unit;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Plugins\POS\Contract\PaymentSourceProviderInterface;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceAvailability;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceContext;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceManager;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceResult;
use PHPUnit\Framework\TestCase;

class PaymentSourceManagerTest extends TestCase
{
    public function testRegistryIgnoresEmptyCodeAndDuplicates(): void
    {
        $manager = new PaymentSourceManager();
        $manager->register(new FakeProvider(''));
        $a = new FakeProvider('customer-account');
        $b = new FakeProvider('customer-account');

        $manager->register($a);
        $manager->register($b);

        $this->assertSame([$a], $manager->getProviders());
    }

    public function testExternalProvidersAreAggregated(): void
    {
        $manager = new PaymentSourceManager();
        $a = new FakeProvider('a');
        $b = new FakeProvider('b');

        $manager->registerExternalProviders([$a, $b, $a]);

        $codes = array_map(static fn($p) => $p->getCode(), $manager->getProviders());
        $this->assertSame(['a', 'b'], $codes);
    }

    public function testCheckAvailabilityCatchesExceptions(): void
    {
        $manager = new PaymentSourceManager();
        $manager->register(new ThrowingProvider('broken'));

        $context = $this->context();
        $result = $manager->checkAvailability($context);

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]['available']);
        $this->assertSame('ERROR', $result[0]['status']);
        $this->assertNull($result[0]['message']);
    }

    public function testApplySourcesRejectsWhenProviderRefuses(): void
    {
        $manager = new PaymentSourceManager();
        $manager->register(new FakeProvider('a', approved: false));

        $context = $this->context();
        $results = $manager->applySources($context, [['code' => 'a', 'amount' => 10.0]]);

        $this->assertFalse($results[0]['approved']);
        $this->assertSame('REJECTED', $results[0]['status']);
    }

    public function testApplySourcesIgnoresUnknownProviders(): void
    {
        $manager = new PaymentSourceManager();
        $results = $manager->applySources($this->context(), [
            ['code' => 'ghost', 'amount' => 10.0],
        ]);

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]['approved']);
        $this->assertSame('NOT_AVAILABLE', $results[0]['status']);
    }

    private function context(): PaymentSourceContext
    {
        return new PaymentSourceContext(
            document: $this->createMock(SalesDocument::class),
            customerCode: 'CUST-001',
            total: 100.0,
            coveredAmount: 0.0,
        );
    }
}

final class FakeProvider implements PaymentSourceProviderInterface
{
    public function __construct(
        private string $code,
        private bool $approved = true,
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDefinition(): array
    {
        return ['code' => $this->code, 'label' => $this->code, 'icon' => 'fa-foo'];
    }

    public function checkAvailability(PaymentSourceContext $context): PaymentSourceAvailability
    {
        return PaymentSourceAvailability::allowed(50.0);
    }

    public function apply(PaymentSourceContext $context, float $amount): PaymentSourceResult
    {
        return $this->approved
            ? PaymentSourceResult::approved($amount)
            : PaymentSourceResult::rejected('REJECTED', 'nope');
    }
}

final class ThrowingProvider implements PaymentSourceProviderInterface
{
    public function __construct(private string $code)
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDefinition(): array
    {
        return ['code' => $this->code, 'label' => $this->code];
    }

    public function checkAvailability(PaymentSourceContext $context): PaymentSourceAvailability
    {
        throw new \RuntimeException('exploded');
    }

    public function apply(PaymentSourceContext $context, float $amount): PaymentSourceResult
    {
        return PaymentSourceResult::approved(0.0);
    }
}
