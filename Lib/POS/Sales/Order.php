<?php


namespace FacturaScripts\Plugins\POS\Lib\POS\Sales;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Lib\BusinessDocumentFormTools;
use RuntimeException;
use UnexpectedValueException;

class Order
{
    const BASE_BUSINESS_DOCUMENT_CLASS = '\\FacturaScripts\\Core\\Model\\Base\\BusinessDocument';
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';
    const DEFAULT_TRANSACTION = 'FacturaCliente';

    /**
     * @var BusinessDocument
     */
    protected $document;

    /**
     * @var array
     */
    protected $transactionLines;

    /**
     * @var array
     */
    protected $orderPayments;

    /**
     * Transaction constructor.
     * @param OrderRequest $request
     * @param String $transactionType
     */
    public function __construct(OrderRequest $request)
    {
        $transactionType = $request->getOrderType(self::DEFAULT_TRANSACTION);

        $this->initDocument($request->getDocumentData(), $transactionType);
        $this->transactionLines = $request->getProductList();
        $this->orderPayments = $request->getPaymentList();
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
        return $this->orderPayments;
    }

    public function hold(): bool
    {
        $previusLines = $this->document->getLines() ?? [];

        foreach ($previusLines as $line) {
            $line->delete();
        }

        return $this->save();
    }

    /**
     * @return string
     */
    public function recalculate(): string
    {
        $documentTools = new BusinessDocumentFormTools();

        return $documentTools->recalculateForm($this->document, $this->transactionLines);
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        if (false === $this->document->save()) {
            return false;
        }

        foreach ($this->transactionLines as $line) {
            $newLine = $this->document->getNewLine($line);

            if (false === $newLine->save()) {
                $this->document->delete();

                return false;
            }
        }

        $this->recalculateDocument();

        if (false === $this->document->save()) {
            $this->document->delete();

            return false;
        }

        return true;
    }

    protected function initDocument(array $data, string $modelName)
    {
        $className = self::MODEL_NAMESPACE . $modelName;

        if (false === class_exists($className)) {
            throw new RuntimeException("Class $className not exist");
        }

        $this->document = new $className;

        if (false === is_subclass_of($this->document, self::BASE_BUSINESS_DOCUMENT_CLASS)) {
            throw new UnexpectedValueException("Class $className is not a valid BusinessDocument");
        }

        //Load document data
        $this->document->loadFromData($data);

        // Update subject
        $this->document->updateSubject();
    }

    protected function recalculateDocument(): void
    {
        (new BusinessDocumentFormTools())->recalculate($this->document);
    }
}
