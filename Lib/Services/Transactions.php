<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;
use RuntimeException;

/**
 * Application Service for managing POS transactions.
 * Orchestrates document creation, line management, and payment processing.
 */
class Transactions
{
    const string SALES_DOCUMENT_CLASS = SalesDocument::class;
    const string MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

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


    /**
     * Transaction constructor.
     * @param TransactionRequest $request
     */
    public function __construct(TransactionRequest $request)
    {
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

        return [
            'doc' => $this->document->toArray(true),
            'lines' => array_map(function ($line) {
                return $line->toArray(true);
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

        if ($this->prepared) {
            if (!$this->deleteDocumentLines()) {
                return false;
            }
        } else {
            $this->setDocumentLines(true);
        }

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

    protected function setDocumentLines(bool $deleteExisting = false): void
    {
        $this->documentLines = [];

        if ($deleteExisting && !$this->deleteDocumentLines()) {
            throw new RuntimeException('fail-delete-document-lines');
        }

        foreach ($this->products as $product) {
            if (true === empty($product)) {
                continue;
            }

            if (true === isset($product['cantidad'])) {
                $this->documentLines[] = $this->document->getNewLine($product);
                continue;
            }

            $newLine = $this->document->getNewProductLine($product['referencia']);

            if (isset($product['thumbnail'])) {
                $newLine->thumbnail = $product['thumbnail'];
            }

            $this->documentLines[] = $newLine;
        }
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
        $this->document->codpago = $this->getPaymentMethod();
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
