<?php

namespace FacturaScripts\Plugins\POS\Tests\Unit;

use FacturaScripts\Plugins\POS\Lib\Exception\InvalidTransactionException;
use FacturaScripts\Plugins\POS\Lib\Services\PaymentValidator;
use FacturaScripts\Plugins\POS\Lib\Services\PaymentPolicy;
use PHPUnit\Framework\TestCase;

final class PaymentValidatorTest extends TestCase
{
    public function testExceptionKeepsContextOutOfPublicMessage(): void
    {
        $exception = InvalidTransactionException::saveError('fail-update');

        self::assertSame('transaction-save-error', $exception->getMessage());
        self::assertSame(['reason' => 'fail-update'], $exception->getContext());
    }

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

    public function testCustomerAccountSettlementKeepsOnlyRealPayments(): void
    {
        $cases = [
            [[['method' => 'CASH', 'amount' => 1000]], 0, 1000.0, PaymentPolicy::REQUIRED],
            [[], 1000, 0.0, PaymentPolicy::CUSTOMER_ACCOUNT],
            [[['method' => 'CASH', 'amount' => 300]], 700, 300.0, PaymentPolicy::CUSTOMER_ACCOUNT],
            [[['method' => 'CASH', 'amount' => 300], ['method' => 'CARD', 'amount' => 200]], 500, 500.0, PaymentPolicy::CUSTOMER_ACCOUNT],
            [[['method' => 'CASH', 'amount' => 1200, 'change' => 200]], 0, 1000.0, PaymentPolicy::CUSTOMER_ACCOUNT],
        ];
        foreach ($cases as [$payments, $account, $collected, $policy]) {
            $result = $this->validator()->validateSettlement($payments, 1000, $account, $policy);
            self::assertCount(count($payments), $result);
            self::assertSame($collected, (float)array_sum(array_column($result, 'net')));
        }
    }

    public function testRejectsIncompleteOrForbiddenCustomerAccountSettlement(): void
    {
        $cases = [
            [500, PaymentPolicy::CUSTOMER_ACCOUNT, 'payment-total-mismatch'],
            [700, PaymentPolicy::REQUIRED, 'payment-customer-account-not-allowed'],
            [-1, PaymentPolicy::CUSTOMER_ACCOUNT, 'payment-invalid-customer-account'],
            [null, PaymentPolicy::CUSTOMER_ACCOUNT, 'payment-invalid-customer-account'],
        ];
        foreach ($cases as [$account, $policy, $key]) {
            try {
                $this->validator()->validateSettlement([['method' => 'CASH', 'amount' => 300]], 1000, $account, $policy);
                self::fail('Expected settlement validation exception');
            } catch (InvalidTransactionException $exception) {
                self::assertSame($key, $exception->getTranslationKey());
            }
        }
    }

    private function validator(): PaymentValidator
    {
        return new PaymentValidator($this->methods(), 2);
    }

    public function testOptionalAllowsUnpaidAndPartialDocuments(): void
    {
        self::assertSame([], $this->validator()->validateSettlement([], 1000, 0, PaymentPolicy::OPTIONAL));
        $payments = $this->validator()->validateSettlement([
            ['method' => 'CASH', 'amount' => 200],
        ], 1000, 0, PaymentPolicy::OPTIONAL);
        self::assertSame(200.0, $payments[0]['net']);
        $payments = $this->validator()->validateSettlement([
            ['method' => 'CASH', 'amount' => 1200, 'change' => 200],
        ], 1000, 0, PaymentPolicy::OPTIONAL);
        self::assertSame(1000.0, $payments[0]['net']);
    }

    public function testOptionalRejectsMalformedPaymentsAndAccountCharges(): void
    {
        $cases = [
            [[['method' => 'OTHER', 'amount' => 0]], 0],
            [[['method' => 'CASH', 'amount' => 200], ['method' => 'CARD', 'amount' => -200]], 0],
            [[['method' => 'CARD', 'amount' => 1100]], 0],
            [[['method' => 'CARD', 'amount' => 200, 'change' => 10]], 0],
            [[['method' => 'CASH', 'amount' => 200]], 800],
        ];
        foreach ($cases as [$payments, $account]) {
            try {
                $this->validator()->validateSettlement($payments, 1000, $account, PaymentPolicy::OPTIONAL);
                self::fail('Expected optional settlement rejection');
            } catch (InvalidTransactionException $exception) {
                self::assertNotEmpty($exception->getTranslationKey());
            }
        }
    }
}
