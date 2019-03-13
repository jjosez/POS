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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Lib\PosDocumentTools;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\ArqueoPOS;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\TerminalPOS;

/**
 * Controller to edit a single item from the AlbaranCliente model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PointOfSale extends Controller
{
    public $arqueo = false;
    public $agente = false;
    public $cliente;
    public $businessDoc = false;
    public $formaPago;
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
        
        if (!$this->isCashupOpened()) {
            $idterminal = $this->request->query->get('terminal'); 
            $idterminal = ($idterminal) ? $idterminal : $this->request->request->get('terminal');

            if ($idterminal) {
                $this->terminal = (new TerminalPOS)->get($idterminal);   
            }
        }                   

        $action = $this->request->request->get('action');
        switch ($action) {
            case 'open-cashup':
                $this->openCashup();
                break;

            case 'close-cashup':
                $this->closeCashup();
                break;

            case 'save-document':
                $this->saveDocument();
                break;

            case 'recalculate-document':
                $this->recalculateDocument();
                return false;
            
            default:
                # code...
                break;
        }
    }

    /**
     * Close current cashup.
     *
     * @return void
     */
    private function closeCashup()
    {
        $terminal = $this->request->request->get('terminal'); 
        $this->terminal = (new TerminalPOS)->get($terminal); 

        if ($this->isCashupOpened()) {
            $this->arqueo->abierto = false;
            $this->arqueo->fechafin = date('d-m-Y');
            $this->arqueo->horafin = date('H:i:s');

            if ($this->arqueo->save()) {
                $this->terminal->disponible = true;
                $this->terminal->save();
            }
        }               
    }

    /**
     * Initialize a common values.
     *
     * @return void
     */
    private function initValues()
    {
        $this->assets();

        $this->cliente = new Cliente();
        $this->formaPago = new FormaPago();
    }

    /**
     * Verify if a cashup is opened by user or terminalpos
     *
     * @return bool
     */
    private function isCashupOpened()
    {   
        if ($this->terminal) {
            $this->arqueo = (new ArqueoPOS)->isOpened('terminal', $this->terminal->idterminal);
        } else {
            $this->arqueo = (new ArqueoPOS)->isOpened('user', $this->user->nick);           
        }        

        if ($this->arqueo) {
            if (!$this->terminal) {
                $this->terminal = (new TerminalPOS)->get($this->arqueo->idterminal);
            }
            
            return true;
        }

        if (!$this->terminal) {
            $this->terminal = new TerminalPOS(); 
        }        

        return false;      
    }

    /**
     * Initialize a new cashcount if not exist.
     *
     * @return void
     */
    private function openCashup()
    {
        if ($this->isCashupOpened()) {
            return;
        }               

        if (!$this->terminal) {
            $this->miniLog->warning($this->i18n->trans('cash-register-not-found'));
            return;           
        }

        $saldoinicial = $this->request->request->get('saldoinicial');

        $this->arqueo = new ArqueoPOS();
        $this->arqueo->abierto = true;
        $this->arqueo->idterminal = $this->terminal->idterminal;
        $this->arqueo->nickusuario = $this->user->nick;
        $this->arqueo->saldoinicial = $saldoinicial;
        $this->arqueo->saldofinal = $saldoinicial;

        if ($this->arqueo->save()) { 
            $msg = $this->terminal->nombre . ' iniciada con: ' . $saldoinicial . ', por ' . $this->user->nick;           
            $this->miniLog->info($msg);
            $this->terminal->disponible = false;

            $this->terminal->save();
            return;
        }

        $this->arqueo = false;
    }

    /**
     * Recalculate pos document, pos document lines from form data.
     *
     * @return void
     */
    private function recalculateDocument()
    {
        $this->setTemplate(false);
              
        $data = $this->request->request->all();
        $modelName = 'FacturaCliente';

        $tools = new PosDocumentTools();  
        $result = $tools->recalculateData($modelName, $data);
        $this->response->setContent($result);

        return false;
    }

    /**
     * Process pos document.
     *
     * @return void
     */
    private function saveDocument()
    {
        if (!$this->permissions->allowUpdate) {
            $this->response->setContent($this->i18n->trans('not-allowed-modify'));
            return false;
        }

        $tools = new PosDocumentTools();
        $data = $this->request->request->all();
        $payments = json_decode($data['payments'], true);
        
        $modelName = 'FacturaCliente';
        $className = 'FacturaScripts\\Dinamic\\Model\\' . $modelName;        
        $document = new $className();        

        if ($tools->processDocumentData($document, $data, $this->miniLog)) {
            if (!$document->save()) {
                $this->miniLog->info(print_r($data, true));
            }
            $this->businessDoc = $document;
        }

        if ($payments['method'] == AppSettings::get('pointofsale','fpagoefectivo') ) {
            $this->arqueo->saldofinal += (float) ($payments['amount'] - $payments['change']);
            $this->arqueo->save(); 
        }     


        $lines = json_decode($data['lines']);
        $this->miniLog->info('Generado documento ' . $document->codigo);
        //$this->miniLog->info(print_r($lines, true));
    }

    protected function assets()
    {
        AssetManager::add('css', FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.css');
        AssetManager::add('js', FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.js');
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/PosDocumentView.js');
    }
}
