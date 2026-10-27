<?php

declare(strict_types=1);

namespace FacturaScripts\Plugins\POS\Tests\Unit;

use FacturaScripts\Plugins\POS\Lib\Customer\CustomerSearchResults;
use PHPUnit\Framework\TestCase;
use stdClass;

final class CustomerSearchResultsTest extends TestCase
{
    public function testKeepsOriginalShapeWithoutDetails(): void
    {
        $customer = new stdClass();
        $customer->code = 'CUST-001';
        $customer->description = 'First customer';

        $results = new CustomerSearchResults([$customer]);

        $this->assertSame([
            ['code' => 'CUST-001', 'description' => 'First customer'],
        ], $results->toArray());
        $this->assertSame(['CUST-001'], $results->customerCodes());
    }

    public function testAppendsMultipleDetails(): void
    {
        $results = new CustomerSearchResults([
            ['code' => 'CUST-001', 'description' => 'First customer'],
        ]);

        $this->assertTrue($results->addDetail('CUST-001', [
            'label' => 'Balance',
            'value' => '125.00',
            'tone' => 'warning',
        ]));
        $this->assertTrue($results->addDetail('CUST-001', [
            'label' => 'Available credit',
            'value' => '500.00',
            'tone' => 'success',
        ]));

        $this->assertSame([
            ['label' => 'Balance', 'value' => '125.00', 'tone' => 'warning'],
            ['label' => 'Available credit', 'value' => '500.00', 'tone' => 'success'],
        ], $results->toArray()[0]['details']);
    }

    public function testRejectsUnknownCustomerAndMissingValue(): void
    {
        $results = new CustomerSearchResults([
            ['code' => 'CUST-001', 'description' => 'First customer'],
        ]);

        $this->assertFalse($results->addDetail('UNKNOWN', ['value' => '10']));
        $this->assertFalse($results->addDetail('CUST-001', ['label' => 'Balance']));
        $this->assertArrayNotHasKey('details', $results->toArray()[0]);
    }

    public function testFallsBackToDefaultTone(): void
    {
        $results = new CustomerSearchResults([
            ['code' => 'CUST-001', 'description' => 'First customer'],
        ]);

        $results->addDetail('CUST-001', [
            'value' => 'Custom information',
            'tone' => 'unsupported',
        ]);

        $this->assertSame('default', $results->toArray()[0]['details'][0]['tone']);
    }

    public function testNumericCustomerCodesAreReturnedAsStrings(): void
    {
        $results = new CustomerSearchResults([
            ['code' => 123, 'description' => 'Numeric customer'],
        ]);

        $this->assertSame(['123'], $results->customerCodes());
        $this->assertTrue($results->addDetail($results->customerCodes()[0], [
            'value' => '10.00',
        ]));
    }
}
