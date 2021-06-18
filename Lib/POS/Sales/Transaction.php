<?php


namespace FacturaScripts\Plugins\POS\Lib\POS\Sales;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Lib\BusinessDocumentFormTools;
use RuntimeException;
use UnexpectedValueException;

class Transaction
{
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';
    const BASE_BUSINESS_DOCUMENT_CLASS = '\\FacturaScripts\\Core\\Model\\Base\\BusinessDocument';

    /**
     * @var BusinessDocument
     */
    protected $document;

    /**
     * @var array
     */
    protected $transactionLines;

    /**
     * Transaction constructor.
     * @param TransactionRequest $request
     * @param String $transactionModelName
     */
    public function __construct(TransactionRequest $request, String $transactionModelName)
    {
        $this->initDocument($request->getDocumentData(), $transactionModelName);
        $this->transactionLines = $request->getLinesData();
    }

    public function hold()
    {
        $previusLines = $this->document->getLines() ?? [];

        foreach ($previusLines as $line) {
            $line->delete();
        }

        return $this->save();
    }

    /**
     * @return BusinessDocument
     */
    public function getDocument(): BusinessDocument
    {
        return $this->document;
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

    protected function initDocument(array $data, $modelName)
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
        $documentTools = new BusinessDocumentFormTools();

        $documentTools->recalculate($this->document);
    }
}