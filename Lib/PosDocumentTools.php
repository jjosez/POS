<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\Serie;

/**
 * A set of tools to recalculate Point of Sale documents.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PosDocumentTools
{
    private $tools;

    public function __construct($width = null, $comands = FALSE) 
    {
        $this->tools = new BusinessDocumentTools();
    }

    private function getColumns()
    {
        $columns = [ 
            "referencia"=> null,
            "descripcion"=> null,
            "cantidad"=> null,
            "servido"=> null,
            "pvpunitario"=> null,
            "dtopor"=> null,
            "pvptotal"=> null,
            "iva"=> null,
            "recargo"=> null,
            "irpf"=> null,
        ];

        return $columns;
    }

    private function processLines(array $formLines)
    {
        $newLines = [];
        $order = count($formLines);
        foreach ($formLines as $data) {
            $line = ['orden' => $order];
            foreach ($this->getColumns() as $key => $value) {
                $line[$key] = isset($data[$key]) ? $data[$key] : null;
            }
            $newLines[] = $line;
            $order--;
        }
        return $newLines;
    }

    public function recalculateData($modelName, $data)
    {
        $className = 'FacturaScripts\\Dinamic\\Model\\' . $modelName;        
        $model = new $className();

        /// gets data form and separate lines data
        $lines = isset($data['lines']) ? $this->processLines($data['lines']) : [];
        unset($data['lines']);

        /// load model data
        $model->loadFromData($data, ['action']);

        /// recalculate with bussines tools
        $result = $this->tools->recalculateForm($model, $lines);
        return $result;
    }

    public function processDocumentData(&$document, $data, &$miniLog)
    {
        $continuar = true;

        $almacen = (new Almacen)->get($data['codalmacen']);
        if (!$almacen) {
            $continuar = false;
        }

        $cliente = (new Cliente)->get($data['codcliente']);
        if (!$cliente) {
            $continuar = false;
        }

        $serie = (new Serie)->get($data['codserie']);
        if (!$serie) {
            $continue = false;
        }

        $pagos = json_decode($data['payments'], true);
        $formaPago = (new FormaPago)->get($pagos['method']);
        if (!$formaPago) {
            $continuar = false;
        }

        if (!$continuar) {
            return false;
        }

        $document->setSubject($cliente);
        $document->codalmacen = $almacen->codalmacen;
        $document->codserie = $serie->codserie;
        $document->codpago = $formaPago->codpago;
        $document->fecha = $data['documentdate'];                

        if ($document->save()) {
            $lineClassName = 'FacturaScripts\\Dinamic\\Model\\Linea' . $document->modelClassName(); 
            foreach (json_decode($data["lines"], true) as $line) {
                unset($line['actualizastock']);
                $newLine = $document->getNewLine($line);

                if (!$newLine->save()) {
                    $miniLog->info( print_r($line, true));  
                    return false;
                }
                $newLine->updateStock($document->codalmacen);                             
            }

            $this->tools = new BusinessDocumentTools();
            $this->tools->recalculate($document);
            return true;
        }

        return false;
    }
}
