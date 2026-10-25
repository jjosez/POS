<?php

namespace FacturaScripts\Plugins\POS\Tests\Unit;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Plugins\POS\Contract\PaymentSourceInterface;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceAvailability;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceContext;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceContextFactory;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceDefinition;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceEntry;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSourceManager;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceResult;
use PHPUnit\Framework\TestCase;

class PaymentSourceManagerTest extends TestCase
{
    public function testRegistryIgnoresEmptyCodeAndDuplicates(): void
    {
        $manager = $this->manager();
        $manager->register(new Fake(''));
        $a = new Fake('customer-account');
        $b = new Fake('customer-account');

        $manager->register($a);
        $manager->register($b);

        $this->assertSame([$a], $manager->getProviders());
    }

    public function testExternalProvidersAreAggregated(): void
    {
        $manager = $this->manager();
        $a = new Fake('a');
        $b = new Fake('b');

        $manager->registerExternalProviders([$a, $b, $a]);

        $codes = array_map(static fn($p) => $p->getCode(), $manager->getProviders());
        $this->assertSame(['a', 'b'], $codes);
    }

    public function testCheckAvailabilityCatchesExceptions(): void
    {
        $manager = $this->manager();
        $manager->register(new Throwing('broken'));

        $context = $this->context();
        $result = $manager->checkAvailability($context);

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]['available']);
        $this->assertSame('ERROR', $result[0]['status']);
        $this->assertNull($result[0]['message']);
    }

    public function testApplySourcesRejectsWhenProviderRefuses(): void
    {
        $manager = $this->manager();
        $manager->register(new Fake('a', approved: false));

        $context = $this->context();
        $results = $manager->applySources($context, [['code' => 'a', 'amount' => 10.0]]);

        $this->assertFalse($results[0]['approved']);
        $this->assertSame('REJECTED', $results[0]['status']);
    }

    public function testApplySourcesIgnoresUnknownProviders(): void
    {
        $manager = $this->manager();
        $results = $manager->applySources($this->context(), [
            ['code' => 'ghost', 'amount' => 10.0],
        ]);

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]['approved']);
        $this->assertSame('NOT_AVAILABLE', $results[0]['status']);
    }

    public function testRequestedAmountIgnoresInvalidSources(): void
    {
        $manager = $this->manager();

        $amount = $manager->requestedAmount([
            ['code' => 'credit', 'amount' => 25.5],
            ['code' => '', 'amount' => 10],
            ['code' => 'negative', 'amount' => -5],
            'invalid',
        ]);

        $this->assertSame(25.5, $amount);
    }

    public function testEntryPreservesReferenceAndMetadata(): void
    {
        $entry = PaymentSourceEntry::fromArray([
            'code' => 'coupon',
            'amount' => 25.5,
            'reference' => 'CP-001',
            'metadata' => ['campaign' => 'summer'],
        ]);

        $this->assertInstanceOf(PaymentSourceEntry::class, $entry);
        $this->assertSame('CP-001', $entry->reference);
        $this->assertSame(['campaign' => 'summer'], $entry->metadata);
        $this->assertSame([
            'code' => 'coupon',
            'amount' => 25.5,
            'reference' => 'CP-001',
            'metadata' => ['campaign' => 'summer'],
        ], $entry->toArray());
    }

    public function testProviderReceivesDuplicateCodesAsSeparateEntries(): void
    {
        $manager = $this->manager();
        $provider = new EntryFake('coupon');
        $manager->register($provider);

        $results = $manager->applySources($this->context(), [
            [
                'code' => 'coupon',
                'amount' => 100.0,
                'reference' => '12345',
                'metadata' => ['campaign' => 'summer'],
            ],
            ['code' => 'coupon', 'amount' => 50.0, 'reference' => '67890'],
        ]);

        $this->assertCount(2, $provider->entries);
        $this->assertSame('12345', $provider->entries[0]->reference);
        $this->assertSame(['campaign' => 'summer'], $provider->entries[0]->metadata);
        $this->assertSame('67890', $provider->entries[1]->reference);
        $this->assertSame('12345', $results[0]['reference']);
        $this->assertSame('67890', $results[1]['reference']);
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

    private function manager(): PaymentSourceManager
    {
        $terminal = $this->createMock(TerminalPuntoVenta::class);
        return new PaymentSourceManager(new PaymentSourceContextFactory($terminal));
    }
}

final class Fake implements PaymentSourceInterface
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

    public function getDefinition(): PaymentSourceDefinition
    {
        return new PaymentSourceDefinition(
            code: $this->code,
            label: $this->code,
            description: '',
            icon: 'fa-foo',
            metadata: [],
        );
    }

    public function checkAvailability(PaymentSourceContext $context): PaymentSourceAvailability
    {
        return PaymentSourceAvailability::allowed(50.0);
    }

    public function apply(PaymentSourceContext $context, PaymentSourceEntry $entry): PaymentSourceResult
    {
        return $this->approved
            ? PaymentSourceResult::approved($entry->amount)
            : PaymentSourceResult::rejected('REJECTED', 'nope');
    }
}

final class Throwing implements PaymentSourceInterface
{
    public function __construct(private string $code)
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDefinition(): PaymentSourceDefinition
    {
        return new PaymentSourceDefinition(
            code: $this->code,
            label: $this->code,
            description: '',
            icon: '',
            metadata: [],
        );
    }

    public function checkAvailability(PaymentSourceContext $context): PaymentSourceAvailability
    {
        throw new \RuntimeException('exploded');
    }

    public function apply(PaymentSourceContext $context, PaymentSourceEntry $entry): PaymentSourceResult
    {
        return PaymentSourceResult::approved(0.0);
    }
}

final class EntryFake implements PaymentSourceInterface
{
    /** @var PaymentSourceEntry[] */
    public array $entries = [];

    public function __construct(private string $code)
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDefinition(): PaymentSourceDefinition
    {
        return new PaymentSourceDefinition($this->code, $this->code, '', '', []);
    }

    public function checkAvailability(PaymentSourceContext $context): PaymentSourceAvailability
    {
        return PaymentSourceAvailability::unlimited();
    }

    public function apply(
        PaymentSourceContext $context,
        PaymentSourceEntry $entry
    ): PaymentSourceResult {
        $this->entries[] = $entry;
        return PaymentSourceResult::approved($entry->amount);
    }
}
