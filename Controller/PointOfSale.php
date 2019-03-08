<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\ArqueoPOS;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\TerminalPOS;

/**
 * Controller to edit a single item from the AlbaranCliente model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PointOfSale extends Controller
{
    public $arqueo = false;
    public $agente = false;
    public $cliente;
    public $terminal;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'point-of-sale';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-shopping-cart';
        $pagedata['showonmenu'] = true;

        return $pagedata;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        
        $this->initValues();
        $this->isCashCountOpened();

        $terminal = $this->request->query->get('terminal');        
        if ($terminal) {
            $this->terminal = (new TerminalPOS)->get($terminal);           
        }

        $action = $this->request->request->get('action');
        switch ($action) {
            case 'iniciararqueo':
                $this->initCashCount();
                break;

            case 'cerrararqueo':
                $this->closeCashCount();
                break;

            case 'save-document':
                $this->saveSalesDocument();
                break;

            case 'recalculate-document':
                $this->recalculateDocumentAction();
                return false;
            
            default:
                # code...
                break;
        }
    }

    /**
     * Initialize a new cashcount if not exist.
     *
     * @return void
     */
    private function initCashCount()
    {
        //$codagente = $this->request->request->get('codagente');
        //$agente = $this->agente->loadFromCode($codagente);

        if ($this->isCashCountOpened()) {
            return;
        }

        $saldoinicial = $this->request->request->get('saldoinicial');

        $this->arqueo = new ArqueoPOS();
        $this->arqueo->abierto = true;
        $this->arqueo->idterminal = $this->terminal->idterminal;
        $this->arqueo->nickusuario = $this->user->nick;
        $this->arqueo->saldoinicial = $saldoinicial;

        if ($this->arqueo->save()) {            
            $this->miniLog->info('Caja iniciada con: ' . $saldoinicial . ' por ' . $this->user->nick);
            $this->terminal->disponible = false;

            $this->terminal->save();
            return;
        }

        $this->arqueo = false;
    }

    /**
     * Initialize common values.
     *
     * @return array
     */
    private function initValues()
    {
        $this->assets();

        $this->cliente = new Cliente();
    }

    /**
     * Verify if a cashcount is opened by user or terminalpos
     *
     * @return bool
     */
    private function isCashCountOpened()
    {   
        if ($this->terminal) {
            $this->arqueo = (new ArqueoPOS)->isOpened('terminal', $this->terminal->idterminal);
        } else {
            $this->arqueo = (new ArqueoPOS)->isOpened('user', $this->user->nick);           
        }        

        if ($this->arqueo) {
            //$this->miniLog->info(print_r($this->arqueo, true));
            $this->terminal = (new TerminalPOS)->get($this->arqueo->idterminal);
            return true;
        }

        if (!$this->terminal) {
            $this->terminal = new TerminalPOS(); 
        }        

        return false;      
    }

    /**
     * Close current cashcount.
     *
     * @return void
     */
    private function closeCashCount()
    {
        $terminal = $this->request->request->get('terminal'); 
        $this->terminal = (new TerminalPOS)->get($terminal); 

        if ($this->isCashCountOpened()) {
            $this->arqueo->abierto = false;
            $this->arqueo->fechafin = date('d-m-Y');
            $this->arqueo->horafin = date('H:i:s');

            if ($this->arqueo->save()) {
                $this->terminal->disponible = true;
                $this->terminal->save();
            }
        }               
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

        /*$columns = [
            {
                "data":"referencia",
                "type":"autocomplete",
                "source":{"source":"Variante","fieldcode":"referencia","fieldtitle":"referencia"},
                "strict":false,
                "visibleRows":5,
                "trimDropdown":false
            },
            {
                "data":"descripcion",
                "type":"text"
            },
            {
                "data":"cantidad",
                "type":"numeric",
                "numericFormat":{"pattern":"0.00"}
            },
            {
                "data":"pvpunitario",
                "type":"numeric",
                "numericFormat":{"pattern":"0.00"}
            },
            {
                "data":"pvptotal",
                "type":"numeric",
                "numericFormat":{"pattern":"0.00"}
            }
        ];*/
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

    private function recalculateDocumentAction()
    {
        $this->setTemplate(false);

        $model = new FacturaCliente();
        $documentTools = new BusinessDocumentTools();

        /// gets data form and separate lines data
        $data = $this->request->request->all();
        $lines = isset($data['lines']) ? $this->processLines($data['lines']) : [];
        unset($data['lines']);

        /// load model data
        $model->loadFromData($data, ['action']);

        /// recalculate
        $result = $documentTools->recalculateForm($model, $lines);
        $this->response->setContent($result);
        return false;
    }

    private function saveSalesDocument()
    {
        //$this->setTemplate(false);
        if (!$this->permissions->allowUpdate) {
            $this->response->setContent($this->i18n->trans('not-allowed-modify'));
            return false;
        }

        $data = $this->request->request->all();

        $this->miniLog->info(print_r($data, true));

        $result = 'OK:' . $this->url();
        $this->response->setContent($result);
    }

    protected function assets()
    {
        AssetManager::add('css', FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.css');
        AssetManager::add('js', FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.js');
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/POSDocumentView.js');
    }
}
