<?php
/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\EasyPOS\POS\Lib\POS;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;

/**
 * Class helper for sale lines operations.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class SalesLinesProcessor
{
    private $data;
    private $document;

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
    }

    /**
     * Initialize BusinessDocumnet with model name passed or FacturaCliente if not exists.
     *
     * @param string $modelName
     */
    private function setDocument(string $modelName)
    {
        $modelName = (empty($modelName)) ? 'FacturaCliente' : $modelName;

        $className = 'FacturaScripts\\Dinamic\\Model\\' . $modelName;
        $this->document = class_exists($className) ? new $className : false;
    }

    /**
     * Load data on Document
     *
     * @param array $data
     */
    private function setDocumentData(array $data)
    {
        $this->data = $this->getBusinessFormData($data);
        $merged = array_merge($data['custom'], $data['final'], $data['form'], $data['subject']);

        $this->loadFromData($this->document, $merged);
    }

    private function getBusinessFormData($request)
    {
        $data = ['custom' => [], 'final' => [], 'form' => [], 'lines' => [], 'subject' => []];
        foreach ($request->request->all() as $field => $value) {
            switch ($field) {
                case 'codpago':
                case 'codserie':
                    $data['custom'][$field] = $value;
                    break;

                case 'idestado':
                    $data['final'][$field] = $value;
                    break;

                case 'lines':
                    $data['lines'] = $this->processFormLines($value);
                    break;

                case 'codcliente':
                    $data['subject'][$field] = $value;
                    break;

                default:
                    $data['form'][$field] = $value;
            }
        }

        return $data;
    }

    /**
     * Process form lines to add missing data from data form.
     * Also adds order column.
     *
     * @param array $formLines
     *
     * @return array
     */
    private function processFormLines(array $formLines)
    {
        $newLines = [];
        $order = count($formLines);
        foreach ($formLines as $line) {
            if (is_array($line)) {
                $line['orden'] = $order;
                $newLines[] = $line;
                $order--;
                continue;
            }

            /// empty line
            $newLines[] = ['orden' => $order];
            $order--;
        }

        return $newLines;
    }

    /**
     * Verifies the structure and loads into the model the given data array
     *
     * @param BusinessDocument $model
     * @param array $data
     */
    private function loadFromData(BusinessDocument &$model, array &$data)
    {
        $model->loadFromData($data, ['action']);
    }

    /**
     * @return BusinessDocument document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Recalculate the document total based on lines.
     *
     * @param array $request
     * @return string
     */
    public function recalculateDocument()
    {
        /*upsdate subject*/
        if (!$this->document->exists()) {
            $this->document->updateSubject();
        }

        /*recalculate*/
        return (new BusinessDocumentTools)->recalculateForm($this->document, $this->data['lines']);
    }
}
