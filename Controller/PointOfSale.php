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
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\ArqueoPOS;
use FacturaScripts\Dinamic\Model\Cliente;
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

        $this->assets();
        $this->cliente = new Cliente();

        $this->isCashCountOpened();

        $terminal = $this->request->query->get('terminal');        
        if ($terminal) {
            $this->terminal = (new TerminalPOS)->get($terminal);           
        }

        $accion = $this->request->request->get('accion');
        switch ($accion) {
            case 'iniciararqueo':
                $this->initCashCount();
                break;

            case 'cerrararqueo':
                $this->closeCashCount();
                break;
            
            default:
                # code...
                break;
        }

        //$this->isCashCountOpened();
    }

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

    protected function assets()
    {
        AssetManager::add('css', FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.css');
        AssetManager::add('js', FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.js');
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/BusinessDocumentView.js');
    }
}
