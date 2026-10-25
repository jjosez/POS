<?php

declare(strict_types=1);

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

/**
 * One concrete application of a Payment Source within a checkout.
 */
final readonly class PaymentSourceEntry
{
    public function __construct(
        public string $code,
        public float $amount,
        public ?string $reference = null,
        public array $metadata = [],
    ) {
        if ($code === '') {
            throw new \InvalidArgumentException('Payment Source code cannot be empty.');
        }
        if (!is_finite($amount) || $amount <= 0.0) {
            throw new \InvalidArgumentException('Payment Source amount must be finite and greater than zero.');
        }
    }

    public static function fromArray(array $data): ?self
    {
        $code = trim((string)($data['code'] ?? ''));
        $amount = (float)($data['amount'] ?? 0.0);
        if ($code === '' || !is_finite($amount) || $amount <= 0.0) {
            return null;
        }

        $reference = isset($data['reference']) && is_scalar($data['reference'])
            ? trim((string)$data['reference'])
            : null;

        return new self(
            code: $code,
            amount: $amount,
            reference: $reference !== '' ? $reference : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'amount' => $this->amount,
            'reference' => $this->reference,
            'metadata' => $this->metadata,
        ];
    }
}
