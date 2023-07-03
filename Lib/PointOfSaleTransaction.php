<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;
use RuntimeException;

class PointOfSaleTransaction
{
    const SALES_DOCUMENT_CLASS = '\\FacturaScripts\\Core\\Model\\Base\\SalesDocument';
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * @var SalesDocument
     */
    protected $document;

    /**
     * @var SalesDocumentLine;
     */
    protected $documentLines = [];

    /**
     * @var PagoPuntoVenta[]
     */
    protected $payments = [];

    /**
     * @var array
     */
    protected $products = [];


    /**
     * Transaction constructor.
     * @param PointOfSaleRequest $request
     */
    public function __construct(PointOfSaleRequest $request)
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

        return ['doc' => $this->document, 'lines' => $this->documentLines];
    }

    /**
     * @return bool
     */
    public function saveDocument(): bool
    {
        if (empty($this->document->primaryColumnValue()) && false === $this->document->save()) {
            return false;
        }

        $this->setDocumentLines();
        $this->setPaymentMethod();

        return Calculator::calculate($this->document, $this->documentLines, true);
    }

    protected function setDocument(array $data, string $modelName)
    {
        $className = self::MODEL_NAMESPACE . $modelName;

        if (false === class_exists($className)) {
            throw new RuntimeException("Class $className not exist");
        }

        $this->document = new $className;

        if (false === is_subclass_of($this->document, self::SALES_DOCUMENT_CLASS)) {
            throw new RuntimeException("Class $className is not a valid SalesDocument");
        }

        //$exclude = ['neto', 'total', 'totalirpf', 'totaliva', 'totalrecargo', 'totalsuplidos'];

        //$this->document->loadFromData($data, $exclude);
        $this->document->loadFromData($data);
        //$this->document->updateSubject();
        $this->setDocumentSubject();
    }

    protected function setDocumentLines()
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

            $this->documentLines[] = $this->document->getNewProductLine($product['referencia']);
        }
    }

    protected function setDocumentSubject()
    {
        if (empty($this->document->nombrecliente) || empty($this->document->cifnif)) {
            $this->document->updateSubject();
        }
    }

    protected function setPayments(array $list)
    {
        foreach ($list as $element) {
            $payment = new PagoPuntoVenta();

            $payment->cantidad = $element['amount'];
            $payment->cambio = $element['change'];
            $payment->codpago = $element['method'];

            $this->payments[] = $payment;
        }
    }

    protected function setPaymentMethod()
    {
        $this->document->codpago = $this->getPaymentMethod();
    }

    protected function getPaymentMethod(): string
    {
        $amount = 0;
        $method = '';
        foreach ($this->payments as $payment) {
            if ($payment->pagoNeto() > $amount) {
                $method = $payment->codpago;
                $amount = $payment->pagoNeto();
            }
        }

        return $method;
    }
}
