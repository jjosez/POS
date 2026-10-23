<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Plugins\POS\Enum\PaymentPolicy;
use RuntimeException;

/**
 * Application Service for managing POS transactions.
 * Orchestrates document creation, line management, and payment processing.
 */
class Transactions
{
    protected const string SALES_DOCUMENT_CLASS = SalesDocument::class;
    protected const string MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * @var SalesDocument
     */
    protected SalesDocument $document;

    /**
     * @var SalesDocumentLine[];
     */
    protected array $documentLines = [];

    /**
     * @var PagoPuntoVenta[]
     */
    protected array $payments = [];

    /**
     * @var array
     */
    protected array $products = [];

    protected bool $prepared = false;

    protected array $rawPayments = [];

    protected PaymentPolicy $paymentPolicy = PaymentPolicy::REQUIRED;

    protected ?TerminalPuntoVenta $terminal;


    /**
     * Transaction constructor.
     * @param TransactionRequest $request
     */
    public function __construct(TransactionRequest $request, ?TerminalPuntoVenta $terminal = null)
    {
        $this->terminal = $terminal;
        $this->setDocument(
            $request->getDocumentData(),
            $request->getDocumentType(),
            $request->isDraft()
        );
        $this->products = $request->getDocumentLinesData();
        $this->rawPayments = $request->getPaymentData();
    }

    /**
     * @return SalesDocument
     */
    public function getDocument(): SalesDocument
    {
        return $this->document;
    }

    public function replaceDocument(array $data, string $modelName): void
    {
        $this->setDocument($data, $modelName);
        $this->prepared = false;
    }

    /**
     * @return PagoPuntoVenta[]
     */
    public function getPayments(): array
    {
        return $this->payments;
    }

    public function getRawPayments(): array
    {
        return $this->rawPayments;
    }

    public function getCollectedAmount(): float
    {
        return array_sum(array_map(static fn(PagoPuntoVenta $payment): float => $payment->pagoNeto(), $this->payments));
    }

    public function getSettledAmount(): float
    {
        return $this->getCollectedAmount();
    }

    public function getPendingAmount(): float
    {
        return (float)$this->document->total - $this->getSettledAmount();
    }

    public function setPaymentPolicy(PaymentPolicy $policy): void
    {
        $this->paymentPolicy = $policy;
    }

    public function getPaymentPolicy(): PaymentPolicy
    {
        return $this->paymentPolicy;
    }

    public function getPaymentData(): array
    {
        return array_map(static function (PagoPuntoVenta $payment): array {
            return [
                'method' => $payment->codpago,
                'amount' => $payment->cantidad,
                'change' => $payment->cambio,
            ];
        }, $this->payments);
    }

    /**
     * @return array
     */
    public function recalculate(): array
    {
        $this->prepareDocument();

        $productMetadata = [];
        foreach ($this->products as $product) {
            $reference = (string)($product['referencia'] ?? '');
            if ($reference === '') {
                continue;
            }
            $productMetadata[$reference] = [
                'stock' => isset($product['stock']) ? (float)$product['stock'] : null,
                'allow_no_stock' => filter_var(
                    $product['allow_no_stock'] ?? false,
                    FILTER_VALIDATE_BOOL
                ),
            ];
        }
        if ($this->terminal !== null) {
            $productsService = new Products($this->terminal);
            foreach (array_keys($productMetadata) as $reference) {
                $availability = $productsService->getAvailability(
                    $reference,
                    (string)$this->document->codalmacen
                );
                if ($availability !== []) {
                    $productMetadata[$reference] = $availability;
                }
            }
        }

        return [
            'doc' => $this->document->toArray(true),
            'lines' => array_map(function ($line) use ($productMetadata) {
                $data = $line->toArray(true);
                $reference = (string)($data['referencia'] ?? '');
                if (isset($productMetadata[$reference])) {
                    $data = array_merge($data, $productMetadata[$reference]);
                }
                return $data;
            }, $this->documentLines),
        ];
    }

    /**
     * @return bool
     */
    public function saveDocument(): bool
    {
        $this->setPaymentMethod();

        if (empty($this->document->id()) && false === $this->document->save()) {
            Tools::log()->warning('record-save-error');
            return false;
        }

        if (!$this->deleteDocumentLines()) {
            return false;
        }

        // Prepared lines were created before the document had its primary key.
        $this->setDocumentLines();

        return Calculator::calculate($this->document, $this->documentLines, true);
    }

    public function prepareDocument(): bool
    {
        $this->setDocumentLines();
        $this->prepared = Calculator::calculate($this->document, $this->documentLines, false);
        return $this->prepared;
    }

    public function setValidatedPayments(array $list): void
    {
        $this->payments = [];

        foreach ($list as $element) {
            $payment = new PagoPuntoVenta();
            $payment->cantidad = $element['amount'];
            $payment->cambio = $element['change'];
            $payment->codpago = $element['method'];
            $payment->isCashMethod = $element['is_cash'];
            $this->payments[] = $payment;
        }
    }

    protected function setDocument(array $data, string $modelName, bool $allowPrimaryKey = false): void
    {
        $className = self::MODEL_NAMESPACE . $modelName;

        if (false === class_exists($className)) {
            throw new RuntimeException("Class $className not exist");
        }

        $this->document = new $className();

        if (false === is_subclass_of($this->document, self::SALES_DOCUMENT_CLASS)) {
            throw new RuntimeException("Class $className is not a valid SalesDocument");
        }

        //$exclude = ['neto', 'total', 'totalirpf', 'totaliva', 'totalrecargo', 'totalsuplidos'];

        //$this->document->loadFromData($data, $exclude);
        $exclude = $allowPrimaryKey ? [] : $this->document::dontCopyFields();
        $this->document->loadFromData($data, $exclude);
        //$this->document->updateSubject();
        $this->setDocumentSubject();
    }

    protected function setDocumentLines(): void
    {
        $this->documentLines = [];

        foreach ($this->products as $product) {
            if (true === empty($product)) {
                continue;
            }

            if (true === isset($product['cantidad'])) {
                $newLine = $this->document->getNewLine($product);
                $this->applyLinePriceRounding($newLine);
                $this->documentLines[] = $newLine;
                continue;
            }

            $newLine = $this->document->getNewProductLine($product['referencia']);

            if (isset($product['pending_quantity'])) {
                $newLine->cantidad = max(0, (float)$product['pending_quantity']);
            }

            if (true === empty($product['referencia'])) {
                $newLine->descripcion = $product['descripcion'];
            }

            if (isset($product['thumbnail'])) {
                $newLine->thumbnail = $product['thumbnail'];
            }

            $this->applyLinePriceRounding($newLine);
            $this->documentLines[] = $newLine;
        }
    }

    protected function applyLinePriceRounding(SalesDocumentLine $line): void
    {
        if ($this->terminal === null) {
            return;
        }

        $line->pvpunitario = LinePriceRounder::adjustNetPrice(
            (float)$line->pvpunitario,
            (float)$line->iva,
            $this->terminal->getLineRoundingMode(),
            $this->terminal->getLineRoundingDecimals()
        );
    }

    protected function deleteDocumentLines(): bool
    {
        foreach ($this->document->getLines() as $line) {
            if (false === $line->delete()) {
                return false;
            }
        }

        return true;
    }

    protected function setDocumentSubject(): void
    {
        if (empty($this->document->nombrecliente) || empty($this->document->cifnif)) {
            $this->document->updateSubject();
        }
    }

    protected function setPaymentMethod(): void
    {
        if ($this->payments !== []) {
            $this->document->codpago = $this->getPaymentMethod();
        }
    }

    protected function getPaymentMethod(): string
    {
        $amount = 0;
        $method = '';
        foreach ($this->payments as $payment) {
            if (abs($payment->pagoNeto()) > $amount) {
                $method = $payment->codpago;
                $amount = abs($payment->pagoNeto());
            }
        }

        return $method;
    }
}
