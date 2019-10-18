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
namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;

/**
 * A set of tools to recalculate Point of Sale document lines.
 *
 * @author Carlos García Gómez   <carlos@facturascripts.com>
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class POSDocumentLineTools
{
    private $model;

    public function __construct($modelName = 'FacturaCliente') 
    {
        $className = 'FacturaScripts\\Dinamic\\Model\\' . $modelName;        
        $this->model = new $className();  
    }

    /**
     * Recalculate the document total based on lines.
     *
     * @return bool
     */
    public function recalculateLines($request)
    {
        $data = $this->getBusinessFormData($request);        
        $merged = array_merge($data['custom'], $data['final'], $data['form'], $data['subject']);

        $this->loadFromData($this->model, $merged);

        /// update subject data?
        if (!$this->model->exists()) {
            $this->model->updateSubject();
        }

        /// recalculate
        return (new BusinessDocumentTools)->recalculateForm($this->model, $data['lines']);
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
     * Verifies the structure and loads into the model the given data array
     *
     * @param array $data
     */
    public function loadFromData(&$model, array &$data)
    {
        $fieldKey = $model->primaryColumn();
        

        $model->loadFromData($data, ['action']);
    }
}
