<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Plugins\POS\Lib\Exception\InvalidTransactionException;

final class PaymentValidator
{
    public const int REFUND = -1;
    public const int SALE = 1;

    private string $cashMethod = '';
    private int $decimals;
    private int $factor;
    private array $supportedMethods = [];

    public function __construct(array $supportedMethods, int $decimals)
    {
        $this->decimals = max(0, $decimals);
        $this->factor = 10 ** $this->decimals;

        foreach ($supportedMethods as $method) {
            $code = trim((string)($method->codpago ?? ''));
            if ('' === $code) {
                continue;
            }

            $this->supportedMethods[$code] = true;
            if ('' === $this->cashMethod && true === (bool)($method->recibecambio ?? false)) {
                $this->cashMethod = $code;
            }
        }
    }

    /**
     * @return array<int, array{method: string, amount: float, change: float, is_cash: bool, net: float}>
     */
    public function validate(array $payments, float $documentTotal, int $operation): array
    {
        if (!in_array($operation, [self::SALE, self::REFUND], true)) {
            throw InvalidTransactionException::paymentError('payment-invalid-operation');
        }

        if (empty($payments)) {
            throw InvalidTransactionException::paymentError('payment-required');
        }

        $expectedMinor = $this->toMinor($documentTotal, 'total');
        if (0 === $expectedMinor || $expectedMinor * $operation <= 0) {
            throw InvalidTransactionException::paymentError('payment-invalid-total');
        }
        $expectedMinor = abs($expectedMinor);

        $changeLines = 0;
        $changeTotal = 0;
        $grossTotal = 0;
        $netTotal = 0;
        $nonCashTotal = 0;
        $seenMethods = [];
        $validated = [];

        foreach ($payments as $index => $payment) {
            if (!is_array($payment)) {
                throw $this->error('payment-invalid-format', $index);
            }

            $method = isset($payment['method']) && is_string($payment['method'])
                ? trim($payment['method'])
                : '';

            if ('' === $method || strlen($method) > 10) {
                throw $this->error('payment-invalid-method', $index);
            }
            if (!isset($this->supportedMethods[$method])) {
                throw $this->error('payment-method-not-supported', $index, ['%method%' => $method]);
            }
            if (isset($seenMethods[$method])) {
                throw $this->error('payment-method-duplicated', $index, ['%method%' => $method]);
            }
            $seenMethods[$method] = true;

            if (!array_key_exists('amount', $payment)) {
                throw $this->error('payment-invalid-amount', $index);
            }

            $amountMinor = $this->toMinor($payment['amount'], 'amount', $index);
            $changeMinor = $this->toMinor($payment['change'] ?? 0, 'change', $index);
            $grossMinor = $amountMinor * $operation;
            $normalizedChange = $changeMinor * $operation;

            if ($grossMinor <= 0 || $normalizedChange < 0) {
                throw $this->error('payment-invalid-sign', $index);
            }
            if ($normalizedChange > $grossMinor) {
                throw $this->error('payment-change-exceeds-amount', $index);
            }

            $isCash = '' !== $this->cashMethod && $method === $this->cashMethod;
            if ($normalizedChange > 0 && !$isCash) {
                throw $this->error('payment-change-not-allowed', $index, ['%method%' => $method]);
            }
            if ($normalizedChange > 0 && ++$changeLines > 1) {
                throw $this->error('payment-change-multiple');
            }

            $netMinor = $grossMinor - $normalizedChange;
            if ($netMinor <= 0) {
                throw $this->error('payment-invalid-amount', $index);
            }

            $grossTotal += $grossMinor;
            $changeTotal += $normalizedChange;
            $netTotal += $netMinor;
            if (!$isCash) {
                $nonCashTotal += $grossMinor;
            }

            $validated[] = [
                'method' => $method,
                'amount' => $this->fromMinor($amountMinor),
                'change' => $this->fromMinor($changeMinor),
                'is_cash' => $isCash,
                'net' => $this->fromMinor($amountMinor - $changeMinor),
            ];
        }

        if ($nonCashTotal > $expectedMinor) {
            throw InvalidTransactionException::paymentError('payment-non-cash-overpayment');
        }

        if ($netTotal !== $expectedMinor || $changeTotal !== max(0, $grossTotal - $expectedMinor)) {
            throw InvalidTransactionException::paymentError('payment-total-mismatch', [
                '%expected%' => $this->formatMinor($expectedMinor),
                '%received%' => $this->formatMinor($netTotal),
            ]);
        }

        return $validated;
    }

    private function error(string $key, int|string|null $index = null, array $context = []): InvalidTransactionException
    {
        if (null !== $index) {
            $context['%index%'] = (string)$index;
        }

        return InvalidTransactionException::paymentError($key, $context);
    }

    private function formatMinor(int $amount): string
    {
        return number_format($amount / $this->factor, $this->decimals, '.', '');
    }

    private function fromMinor(int $amount): float
    {
        return $amount / $this->factor;
    }

    private function toMinor(mixed $value, string $field, int|string|null $index = null): int
    {
        if (is_bool($value) || null === $value || (!is_int($value) && !is_float($value) && !is_string($value))) {
            throw $this->error('payment-invalid-' . $field, $index);
        }

        if (is_string($value)) {
            $value = trim($value);
            if (!preg_match('/^-?\d+(?:\.(\d+))?$/', $value, $matches)) {
                throw $this->error('payment-invalid-' . $field, $index);
            }
            if (isset($matches[1]) && strlen($matches[1]) > $this->decimals) {
                throw $this->error('payment-invalid-precision', $index);
            }
        }

        $number = (float)$value;
        if (!is_finite($number) || abs($number) > PHP_INT_MAX / $this->factor) {
            throw $this->error('payment-invalid-' . $field, $index);
        }

        $minor = (int)round($number * $this->factor);
        $normalized = $minor / $this->factor;
        $epsilon = 1 / ($this->factor * 10000);
        if (abs($number - $normalized) > $epsilon) {
            throw $this->error('payment-invalid-precision', $index);
        }

        return $minor;
    }
}
