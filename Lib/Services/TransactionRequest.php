<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Request;
use FacturaScripts\Plugins\POS\Lib\Exception\InvalidTransactionException;
use RuntimeException;

/**
 * DTO (Data Transfer Object) for transaction requests.
 * Parses and validates incoming HTTP requests for POS transactions.
 */
class TransactionRequest
{
    protected array $documentData = [];
    protected array $documentLinesData = [];
    protected array $paymentData = [];
    protected mixed $customerAccountAmount = 0;
    protected string $documentType = 'FacturaCliente';

    public function __construct(Request $request)
    {
        $data = $this->getContent($request);

        if (empty($data)) {
            throw new RuntimeException('Petición invalida.');
        }

        // Asignar secciones específicas
        $lines = $data['lines'] ?? [];
        $payments = $data['payments'] ?? [];
        if (!is_array($lines)) {
            throw InvalidTransactionException::emptyLines();
        }
        if (!is_array($payments)) {
            throw InvalidTransactionException::paymentError('payment-invalid-format');
        }
        $this->documentLinesData = $lines;
        $this->paymentData = $payments;
        $this->customerAccountAmount = array_key_exists('customerAccountAmount', $data) ? $data['customerAccountAmount'] : 0;

        $this->documentType = $data['tipo-documento'] ?? 'FacturaCliente';

        if (!empty($data['draft'])) {
            $data['generadocumento'] = $this->documentType;
            $this->documentType = $data['tipo-documento'] = 'BorradorPuntoVenta';
        }

        // El resto de los datos se consideran parte del documento
        unset($data['lines'], $data['payments'], $data['tipo-documento'], $data['customerAccountAmount'], $data['payment_policy']);
        $this->documentData = $data;
    }

    public function getDocumentData(): array
    {
        return $this->documentData;
    }

    public function getDocumentLinesData(): array
    {
        return $this->documentLinesData;
    }

    public function getPaymentData(): array
    {
        return $this->paymentData;
    }

    public function getCustomerAccountAmount(): mixed
    {
        return $this->customerAccountAmount;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function isDraft(): bool
    {
        return $this->documentType === 'BorradorPuntoVenta';
    }

    private function getContent(Request $request): array
    {
        $content = $request->getContent();
        $decoded = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }
}
