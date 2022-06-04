<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use RuntimeException;
use UnexpectedValueException;

class PointOfSaleOrder
{
    const BASE_BUSINESS_DOCUMENT_CLASS = '\\FacturaScripts\\Core\\Model\\Base\\BusinessDocument';
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * @var BusinessDocument
     */
    protected $document;

    /**
     * @var BusinessDocumentLine;
     */
    protected $documentLines = [];

    /**
     * @var array
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
        $this->payments = $request->getPaymentList();
        $this->products = $request->getProductList();
    }

    /**
     * @return BusinessDocument
     */
    public function getDocument(): BusinessDocument
    {
        return $this->document;
    }

    /**
     * @return array
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
    public function save(): bool
    {
        if (empty($this->document->primaryColumnValue()) && false === $this->document->save()) {
            return false;
        }

        $this->setDocumentLines();

        return Calculator::calculate($this->document, $this->documentLines, true);
    }

    protected function setDocument(array $data, string $modelName)
    {
        $className = self::MODEL_NAMESPACE . $modelName;

        if (false === class_exists($className)) {
            throw new RuntimeException("Class $className not exist");
        }

        $this->document = new $className;

        if (false === is_subclass_of($this->document, self::BASE_BUSINESS_DOCUMENT_CLASS)) {
            throw new UnexpectedValueException("Class $className is not a valid BusinessDocument");
        }

        $this->document->loadFromData($data);
        $this->document->updateSubject();
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
}
