<?php

namespace FacturaScripts\Plugins\POS\Tests\Unit;

use FacturaScripts\Plugins\POS\Lib\Exception\InvalidTransactionException;
use FacturaScripts\Plugins\POS\Lib\Services\PaymentValidator;
use PHPUnit\Framework\TestCase;

final class PaymentValidatorTest extends TestCase
{
    public function testExactCashPaymentIgnoresClientCashFlag(): void
    {
        $result = $this->validator()->validate([
            ['method' => 'CASH', 'amount' => 10, 'change' => 0, 'is_cash' => false],
        ], 10, PaymentValidator::SALE);

        self::assertTrue($result[0]['is_cash']);
        self::assertSame(10.0, $result[0]['net']);
    }

    public function testSplitPayment(): void
    {
        $result = $this->validator()->validate([
            ['method' => 'CARD', 'amount' => 4, 'change' => 0],
            ['method' => 'CASH', 'amount' => 10, 'change' => 4],
        ], 10, PaymentValidator::SALE);

        self::assertCount(2, $result);
        self::assertSame(6.0, $result[1]['net']);
    }

    public function testClientCannotMarkCardAsCash(): void
    {
        $result = $this->validator()->validate([
            ['method' => 'CARD', 'amount' => 10, 'is_cash' => true],
        ], 10, PaymentValidator::SALE);

        self::assertFalse($result[0]['is_cash']);
    }

    public function testRefundUsesNegativeAmountsAndChange(): void
    {
        $result = $this->validator()->validate([
            ['method' => 'CASH', 'amount' => -20, 'change' => -5],
        ], -15, PaymentValidator::REFUND);

        self::assertSame(-15.0, $result[0]['net']);
        self::assertTrue($result[0]['is_cash']);
    }

    /**
     * @dataProvider invalidPaymentsProvider
     */
    public function testRejectsInvalidPayments(array $payments, float $total, int $operation, string $key): void
    {
        try {
            $this->validator()->validate($payments, $total, $operation);
            self::fail('Expected validation exception');
        } catch (InvalidTransactionException $exception) {
            self::assertSame($key, $exception->getTranslationKey());
        }
    }

    public static function invalidPaymentsProvider(): array
    {
        return [
            'empty' => [[], 10, PaymentValidator::SALE, 'payment-required'],
            'unsupported method' => [[['method' => 'OTHER', 'amount' => 10]], 10, PaymentValidator::SALE, 'payment-method-not-supported'],
            'duplicated method' => [[['method' => 'CARD', 'amount' => 5], ['method' => 'CARD', 'amount' => 5]], 10, PaymentValidator::SALE, 'payment-method-duplicated'],
            'client cash cannot add change' => [[['method' => 'CARD', 'amount' => 12, 'change' => 2, 'is_cash' => true]], 10, PaymentValidator::SALE, 'payment-change-not-allowed'],
            'insufficient payment' => [[['method' => 'CASH', 'amount' => 9]], 10, PaymentValidator::SALE, 'payment-total-mismatch'],
            'positive refund' => [[['method' => 'CASH', 'amount' => 10]], -10, PaymentValidator::REFUND, 'payment-invalid-sign'],
            'scientific notation' => [[['method' => 'CASH', 'amount' => '1e1']], 10, PaymentValidator::SALE, 'payment-invalid-amount'],
            'excess precision' => [[['method' => 'CASH', 'amount' => '10.001']], 10, PaymentValidator::SALE, 'payment-invalid-precision'],
            'infinite amount' => [[['method' => 'CASH', 'amount' => INF]], 10, PaymentValidator::SALE, 'payment-invalid-amount'],
            'card overpayment' => [[['method' => 'CARD', 'amount' => 11]], 10, PaymentValidator::SALE, 'payment-non-cash-overpayment'],
        ];
    }

    public function testSupportsZeroDecimalCurrency(): void
    {
        $validator = new PaymentValidator($this->methods(), 0);
        $result = $validator->validate([
            ['method' => 'CASH', 'amount' => 10],
        ], 10, PaymentValidator::SALE);

        self::assertSame(10.0, $result[0]['amount']);
    }

    public function testSupportsThreeDecimalCurrency(): void
    {
        $validator = new PaymentValidator($this->methods(), 3);
        $result = $validator->validate([
            ['method' => 'CASH', 'amount' => '10.125'],
        ], 10.125, PaymentValidator::SALE);

        self::assertSame(10.125, $result[0]['amount']);
    }

    private function methods(): array
    {
        return [
            (object)['codpago' => 'CASH', 'recibecambio' => true],
            (object)['codpago' => 'CARD', 'recibecambio' => false],
        ];
    }

    private function validator(): PaymentValidator
    {
        return new PaymentValidator($this->methods(), 2);
    }
}
