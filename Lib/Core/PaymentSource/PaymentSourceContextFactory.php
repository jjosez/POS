<?php

namespace FacturaScripts\Plugins\POS\Lib\Core\PaymentSource;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Plugins\POS\Lib\Services\Transactions;

final class PaymentSourceContextFactory
{
    private const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    public function __construct(private readonly TerminalPuntoVenta $terminal)
    {
    }

    public function createDiscovery(array $payload): PaymentSourceContext
    {
        $defaultDocument = $this->terminal->getDefaultDocument();
        $documentType = (string)($payload['documentType'] ?? ($defaultDocument->tipodoc ?? 'FacturaCliente'));
        $document = $this->newDocument($documentType);
        $customerCode = (string)($payload['customerCode'] ?? '');
        $total = (float)($payload['total'] ?? 0.0);
        $coveredAmount = isset($payload['coveredAmount'])
            ? (float)$payload['coveredAmount']
            : max(0.0, $total - (float)($payload['remaining'] ?? $total));

        $document->codcliente = $customerCode;
        $document->codserie = (string)($payload['codserie'] ?? ($defaultDocument->codserie ?? ''));
        $document->coddivisa = (string)($payload['currency'] ?? '');
        $document->idempresa = (int)$this->terminal->idempresa;
        $document->codalmacen = (string)$this->terminal->codalmacen;
        $document->total = $total;

        $extra = $this->terminalData();
        if (isset($payload['idpausada']) && $payload['idpausada'] !== '') {
            $extra['idpausada'] = (int)$payload['idpausada'];
        }

        return new PaymentSourceContext(
            document: $document,
            customerCode: $customerCode !== '' ? $customerCode : null,
            total: $total,
            coveredAmount: $coveredAmount,
            payments: is_array($payload['payments'] ?? null) ? $payload['payments'] : [],
            extra: $extra,
        );
    }

    /**
     * @param array<int, array{code: string, amount: float}> $sources
     */
    public function createForTransaction(Transactions $transaction, array $sources): PaymentSourceContext
    {
        $document = $transaction->getDocument();

        return new PaymentSourceContext(
            document: $document,
            customerCode: (string)($document->codcliente ?? ''),
            total: (float)$document->total,
            sources: $sources,
            extra: $this->terminalData(),
        );
    }

    private function newDocument(string $documentType): SalesDocument
    {
        $class = self::MODEL_NAMESPACE . $documentType;
        if ($documentType === '' || !class_exists($class) || !is_subclass_of($class, SalesDocument::class)) {
            $class = self::MODEL_NAMESPACE . 'FacturaCliente';
        }

        return new $class();
    }

    private function terminalData(): array
    {
        return [
            'terminal' => (int)$this->terminal->idterminal,
            'idempresa' => (int)$this->terminal->idempresa,
            'codalmacen' => (string)$this->terminal->codalmacen,
        ];
    }
}
