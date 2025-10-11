<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Request;
use RuntimeException;

class PointOfSaleRequest
{
    protected array $documentData = [];
    protected array $documentLinesData = [];
    protected array $paymentData = [];
    protected string $documentType = 'FacturaCliente';

    public function __construct(Request $request)
    {
        $data = $this->getContent($request);

        if (empty($data)) {
            throw new RuntimeException('Petición invalida.');
        }

        // Asignar secciones específicas
        $this->documentLinesData = $data['lines'] ?? [];
        $this->paymentData = $data['payments'] ?? [];

        $this->documentType = $data['tipo-documento'] ?? 'FacturaCliente';

        if (!empty($data['draft'])) {
            $data['generadocumento'] = $this->documentType;
            $this->documentType = $data['tipo-documento'] = 'BorradorPuntoVenta';
        }

        // El resto de los datos se consideran parte del documento
        unset($data['lines'], $data['payments'], $data['tipo-documento']);
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
