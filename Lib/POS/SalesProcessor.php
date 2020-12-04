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

    protected $data;
    protected $document;
    protected $tools;
    private $cart;

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
    private function setDocumentData(array $data)
    {
        if ($data['action'] !== ('recalculate-document')) {
            $this->data['lines'] = json_decode($data['lines'], true);
            $this->data['payments'] = json_decode($data['payments'], true);

            unset(
                $data['lines'], $data['payments'], $data['token'], $data['tipo-documento'],
                $data['total'], $data['totaliva'], $data['totalirpf'], $data['neto']);
            $this->data['doc'] = $data;
            return;
        }
        $this->data = $this->getBusinessFormData($data);
    }

    private function getBusinessFormData($formdata)
    {
        $data = ['custom' => [], 'final' => [], 'form' => [], 'lines' => [], 'subject' => []];
        foreach ($formdata as $field => $value) {
            switch ($field) {
                case 'codpago':
                case 'codserie':
                    $data['custom'][$field] = $value;
                    break;

                case 'dtopor1':
                case 'dtopor2':
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
     * @return BusinessDocument document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @return array
     */
    public function getPaymentsData()
    {
        return $this->data['payments'];
    }

    /**
     * Recalculate the document total based on lines.
     *
     * @param array $request
     * @return string
     */
    public function recalculateDocument()
    {
        $merged = array_merge($this->data['custom'], $this->data['final'], $this->data['form'], $this->data['subject']);
        $this->loadFromData($this->document, $merged);

        /*update subject*/
        $this->document->updateSubject();

        /*recalculate*/
        return $this->tools->recalculateForm($this->document, $this->data['lines']);
    }

    /**
     * Verifies the structure and loads into the model the given data array
     *
     * @param BusinessDocument $model
     * @param array $data
     */
    protected function loadFromData(BusinessDocument &$model, array &$data)
    {
        $model->loadFromData($data, ['action']);
    }

    /**
     * Saves the document.
     *
     * @return bool
     */
    public function saveDocument()
    {
        $this->loadFromData($this->document, $this->data['doc']);

        if (false === $this->document->updateSubject()) {
            return false;
        }

        if ($this->document->save()) {
            foreach ($this->data['lines'] as $line) {
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

    /**
     * Saves the document.
     *
     * @return bool
     */
    public function pauseDocument()
    {
        $this->loadFromData($this->document, $this->data['doc']);

        if (false === $this->document->updateSubject()) {
            return false;
        }

        $previusLines = $this->document->getLines();
        if (false === empty($previusLines)) {
            foreach ($previusLines as $line) {
                $line->delete();
            }
        }

        if ($this->document->save()) {
            foreach ($this->data['lines'] as $line) {
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
