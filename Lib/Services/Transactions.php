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
    const string SALES_DOCUMENT_CLASS = '\\FacturaScripts\\Core\\Model\\Base\\SalesDocument';
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


    /**
     * Transaction constructor.
     * @param TransactionRequest $request
     */
    public function __construct(TransactionRequest $request)
    {
        $this->setDocument($request->getDocumentData(), $request->getDocumentType());
        $this->setPayments($request->getPaymentData());

        $this->products = $request->getDocumentLinesData();
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

    /**
     * @return array
     */
    public function recalculate(): array
    {
        $this->setDocumentLines();
        Calculator::calculate($this->document, $this->documentLines, false);

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

        $this->setDocumentLines();

        return Calculator::calculate($this->document, $this->documentLines, true);
    }

    protected function setDocument(array $data, string $modelName): void
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
        $this->document->loadFromData($data);
        //$this->document->updateSubject();
        $this->setDocumentSubject();
    }

    protected function setDocumentLines(): void
    {
        foreach ($this->document->getLines() as $line) {
            $line->delete();
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

    protected function setDocumentSubject(): void
    {
        if (empty($this->document->nombrecliente) || empty($this->document->cifnif)) {
            $this->document->updateSubject();
        }
    }

    protected function setPayments(array $list): void
    {
        foreach ($list as $element) {
            $payment = new PagoPuntoVenta();

            $payment->cantidad = $element['amount'];
            $payment->cambio = $element['change'];
            $payment->codpago = $element['method'];
            $payment->isCashMethod = $element['is_cash'] ?? false;

            $this->payments[] = $payment;
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
                $amount = $payment->pagoNeto();
            }
        }

        return $method;
    }
}
