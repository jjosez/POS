<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Lib\POS;

use FacturaScripts\Dinamic\Lib\BusinessDocumentFormTools;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use function json_decode;

/**
 * Class helper to process POS operations.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class SalesProcessor
{
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * @var BusinessDocument
     */
    protected $document;

    /**
     * @var BusinessDocumentFormTools
     */
    protected $tools;

    /**
     * @var array
     */
    protected $documentData;

    /**
     * @var array
     */
    protected $linesData;

    /**
     * @var array
     */
    protected $paymentsData;

    /**
     * SalesLinesProcessor constructor.
     *
     * @param string $modelName
     * @param array $data
     */
    public function __construct(string $modelName, array $data)
    {
        $this->setDocument($modelName);
        $this->setDocumentData($data);
        $this->tools = new BusinessDocumentFormTools();
    }

    /**
     * Initialize BusinessDocument with model name passed or FacturaCliente if not exists.
     *
     * @param string $modelName
     */
    private function setDocument(string $modelName)
    {
        $modelName = empty($modelName) ? 'FacturaCliente' : $modelName;

        $className = self::MODEL_NAMESPACE . $modelName;
        $this->document = class_exists($className) ? new $className : false;
    }

    /**
     * Load data on Document
     *
     * @param array $data
     */
    protected function setDocumentData(array $data)
    {
        if ('recalculate-document' !== $data['action']) {
            $this->linesData = json_decode($data['lines'], true);
            $this->paymentsData = json_decode($data['payments'], true);

            unset($data['total'], $data['totaliva'], $data['totalirpf'], $data['neto']);
        } else {
            $this->linesData = $data['lines'] ?? [];
        }

        unset($data['action'], $data['lines'], $data['payments']);

        $this->documentData = $data;
    }

    /**
     * @return BusinessDocument document
     */
    public function getDocument(): BusinessDocument
    {
        return $this->document;
    }

    /**
     * @return array
     */
    public function getPayments()
    {
        return $this->paymentsData ?? [];
    }

    /**
     * Recalculate the document total based on lines.
     *
     * @return string
     */
    public function recalculate(): string
    {
        //Load document data
        $this->document->loadFromData($this->documentData);

        // Update subject
        $this->document->updateSubject();

        // Recalculate
        return $this->tools->recalculateForm($this->document, $this->linesData);
    }

    /**
     * Saves the document.
     *
     * @param bool $pause
     * @return bool
     */
    public function saveDocument($pause = false): bool
    {
        $this->document->loadFromData($this->documentData);

        if (false === $this->document->updateSubject()) {
            return false;
        }

        if (true === $pause) {
            $previusLines = $this->document->getLines();
            if (false === empty($previusLines)) {
                foreach ($previusLines as $line) {
                    $line->delete();
                }
            }
        }

        if ($this->document->save()) {
            foreach ($this->linesData as $line) {
                $newLine = $this->document->getNewLine($line);

                if (false === $newLine->save()) {
                    $this->document->delete();
                    return false;
                }
            }
            $this->tools->recalculate($this->document);

            if ($this->document->save()) {
                return true;
            }

            $this->document->delete();
        }

        return false;
    }
}
