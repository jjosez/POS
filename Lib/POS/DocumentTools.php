<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\POS\Lib\POS;

use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\Serie;

/**
 * A set of tools to recalculate Point of Sale document lines.
 *
 * @author Carlos García Gómez   <carlos@facturascripts.com>
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class DocumentTools
{
    private $document;

    public function __construct($modelName = 'FacturaCliente') 
    {
        $className = 'FacturaScripts\\Dinamic\\Model\\' . $modelName;        
        $this->document = new $className();  
    }    
    
    /**
     * 
     * @return array
     */
    protected function getBusinessFormData($request)
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
     * 
     * @return business document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Verifies the structure and loads into the model the given data array
     *
     * @param array $data
     */
    public function loadFromData(&$model, array &$data)
    {
        $fieldKey = $model->primaryColumn();
        

        $model->loadFromData($data, ['action']);
    }  

    /**
     * Verifies the values and generate new business document.
     *
     * @param array $data
     */         
    public function processDocumentData($data)
    {
        $almacen = new Almacen();
        if (!$almacen->loadFromCode($data['codalmacen'])) {
            return false;
        }

        $cliente = new Cliente();
        if (!$cliente->loadFromCode($data['codcliente'])) {
            return false;
        }

        $serie = new Serie();
        if (!$serie->loadFromCode($data['codserie'])) {
            return false;
        }

        $pagos = json_decode($data['payments'], true);
        $formaPago = new FormaPago();
        if (!$formaPago->loadFromCode($pagos['method'])) {
            return false;
        }

        $this->document->setSubject($cliente);
        $this->document->codalmacen = $almacen->codalmacen;
        $this->document->codserie = $serie->codserie;
        $this->document->codpago = $formaPago->codpago;
        $this->document->fecha = $data['documentdate'];                

        if ($this->document->save()) {
            foreach (json_decode($data["lines"], true) as $line) {
                unset($line['actualizastock']);
                $newLine = $this->document->getNewLine($line);

                if (!$newLine->save()) {
                    //$miniLog->info( print_r($line, true));  
                    $this->document->delete();
                    return false;
                }                            
            }

            (new BusinessDocumentTools)->recalculate($this->document);
            

            if ($this->document->save()) {
                return true;
            }

            $this->document->delete();
        }

        return false;
    }

    /**
     * Process form lines to add missing data from data form.
     * Also adds order column.
     *
     * @param array $formLines
     *
     * @return array
     */
    public function processFormLines(array $formLines)
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
     * Recalculate the document total based on lines.
     *
     * @return bool
     */
    public function recalculateDocument($request)
    {
        $data = $this->getBusinessFormData($request);        
        $merged = array_merge($data['custom'], $data['final'], $data['form'], $data['subject']);

        $this->loadFromData($this->document, $merged);

        /// update subject data?
        if (!$this->document->exists()) {
            $this->document->updateSubject();
        }

        /// recalculate
        return (new BusinessDocumentTools)->recalculateForm($this->document, $data['lines']);
    }
}
