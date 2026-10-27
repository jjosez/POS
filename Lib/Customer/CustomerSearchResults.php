<?php

declare(strict_types=1);

namespace FacturaScripts\Plugins\POS\Lib\Customer;

/**
 * Mutable customer search results shared with POS controller extensions.
 *
 * The original payload returned by codeModelSearch() is preserved so the
 * response shape is unchanged when no extension adds information. Extensions
 * append presentation details through addDetail() and may rely on
 * customerCodes() to perform their own batched lookups instead of issuing
 * one query per row.
 */
final class CustomerSearchResults
{
    private const ALLOWED_TONES = ['default', 'info', 'success', 'warning', 'danger'];

    /** @var list<array<string, mixed>> */
    private array $customers = [];

    /** @var array<string, list<int>> */
    private array $indexesByCode = [];

    /**
     * @param iterable<int|string, object|array<string, mixed>> $customers
     */
    public function __construct(iterable $customers = [])
    {
        foreach ($customers as $customer) {
            $item = $this->normalizeCustomer($customer);
            if ($item === null) {
                continue;
            }

            $index = count($this->customers);
            $this->customers[] = $item;

            $code = $this->scalarToString($item['code'] ?? '');
            if ($code !== '') {
                $this->indexesByCode[$code][] = $index;
            }
        }
    }

    /**
     * Adds a safely renderable detail for every row matching the customer code.
     *
     * Supported keys: label (optional), value (required), tone (optional, one
     * of default|info|success|warning|danger).
     */
    public function addDetail(string $customerCode, array $detail): bool
    {
        if (!isset($this->indexesByCode[$customerCode])) {
            return false;
        }

        if (!array_key_exists('value', $detail)) {
            return false;
        }

        $normalized = [
            'label' => $this->scalarToString($detail['label'] ?? ''),
            'value' => $this->scalarToString($detail['value']),
            'tone' => $this->normalizeTone($detail['tone'] ?? 'default'),
        ];

        foreach ($this->indexesByCode[$customerCode] as $index) {
            $this->customers[$index]['details'] ??= [];
            $this->customers[$index]['details'][] = $normalized;
        }

        return true;
    }

    /**
     * Returns the unique customer codes currently indexed as strings.
     *
     * @return list<string>
     */
    public function customerCodes(): array
    {
        $codes = [];
        foreach (array_keys($this->indexesByCode) as $code) {
            $codes[] = (string)$code;
        }
        return $codes;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->customers;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeCustomer(mixed $customer): ?array
    {
        if (is_array($customer)) {
            return $customer;
        }

        if (is_object($customer)) {
            return get_object_vars($customer);
        }

        return null;
    }

    private function normalizeTone(mixed $tone): string
    {
        $value = $this->scalarToString($tone);
        return in_array($value, self::ALLOWED_TONES, true) ? $value : 'default';
    }

    private function scalarToString(mixed $value): string
    {
        return is_scalar($value) || $value === null ? (string)$value : '';
    }
}
