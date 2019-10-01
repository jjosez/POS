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
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;

use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTicket;
use FacturaScripts\Dinamic\Lib\MultiRequestProtection;
use FacturaScripts\Dinamic\Lib\POSDocumentOptions;
use FacturaScripts\Dinamic\Lib\POSDocumentTools;

use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\DenominacionMoneda;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\OperacionPOS;
//use FacturaScripts\Dinamic\Model\PagoCliente;
use FacturaScripts\Dinamic\Model\SesionPOS;
use FacturaScripts\Dinamic\Model\TerminalPOS;
use FacturaScripts\Dinamic\Model\Ticket;

/**
 * Controller to process Point of Sale Operations
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class POS extends Controller
{
    public $arqueo = false;
    public $agente = false;
    public $cliente;
    public $formaPago;
    public $terminal;   
    protected $pageOption;

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
        $this->isSessionOpened();

        $idterminal = $this->request->query->get('terminal');
        ($idterminal) ? $this->terminal->loadFromCode($idterminal) : null;        

        $this->execAction();
    }

    private function execAction()
    {
        $action = $this->request->request->get('action');
        switch ($action) {
            case 'open-till-session':
                $this->openSession();
                break;

            case 'close-till-session':
                $this->closeSession();
                break;

            case 'save-document':
                $this->saveDocument();
                break;

            case 'recalculate-document':
                $this->recalculateDocument();
                return false;
            
            default:
                
                break;
        }
    }    

    /**
     * Initialize default values.
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
     * Close current session.
     *
     * @return void
     */
    private function closeSession()
    {
        if (!$this->isSessionOpened()) {
            $this->miniLog->info('there-is-no-open-till-session');
            return;
        }

        $this->arqueo->abierto = false;
        $this->arqueo->fechafin = date('d-m-Y');
        $this->arqueo->horafin = date('H:i:s');

        $cash = $this->request->request->get('cash');       
        $total = 0.0;

        foreach ($cash as $value => $count) {
            $total += (float) $value * (float) $count;
        }

        $this->miniLog->info('Dinero contado: ' . $total);
        $this->arqueo->saldocontado = $total;
        $this->arqueo->conteo = json_encode($cash);

        if ($this->arqueo->save()) {
            $this->terminal->disponible = true;
            $this->terminal->save();
        }

        $this->isSessionOpened();            
    }

    /**
     * Verify if a till session is opened by User or Point of Sale Terminal
     *
     * @return bool
     */
    private function isSessionOpened()
    {
        $this->arqueo = new SesionPOS();
        $this->terminal = new TerminalPOS();
        $this->setTemplate('\POS\SessionScreen');   

        if (!$this->arqueo->isOpened('user', $this->user->nick)) {
            return false;
        }
        
        if (!$this->terminal->loadFromCode($this->arqueo->idterminal)) {            
            return false;
        }

        $this->setTemplate('\POS\SalesScreen');
        return true;      
    }

    /**
     * Initialize a new till session if not exist.
     *
     * @return void
     */
    private function openSession()
    {
        if ($this->isSessionOpened()) {
            $this->miniLog->info('there-is-an-open-till-session-for-this-user');
            return;
        }

        $idterminal = $this->request->request->get('terminal');  
        if (!$this->terminal->loadFromCode($idterminal)) {
            $this->miniLog->warning($this->i18n->trans('cash-register-not-found')); 
            return;
        }        

        $saldoinicial = $this->request->request->get('saldoinicial');

        $this->arqueo = new SesionPOS();
        $this->arqueo->abierto = true;
        $this->arqueo->idterminal = $this->terminal->idterminal;
        $this->arqueo->nickusuario = $this->user->nick;
        $this->arqueo->saldoinicial = $saldoinicial;
        $this->arqueo->saldoesperado = $saldoinicial;

        if ($this->arqueo->save()) { 
            $msg = $this->terminal->nombre . ' iniciada con: ' . $saldoinicial . ', por ' . $this->user->nick;           
            $this->miniLog->info($msg);
            $this->terminal->disponible = false;

            $this->terminal->save();
            $this->isSessionOpened();
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

        $columns = POSDocumentOptions::getEnabledColumns($this->user);
        $tools = new POSDocumentTools($columns);  
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
        $data = $this->request->request->all(); 

        if (!$this->permissions->allowUpdate) {
            $this->response->setContent($this->i18n->trans('not-allowed-modify'));
            return false;
        }

        $token = $data['token'];
        if (!empty($token) && (new MultiRequestProtection)->tokenExist($token)) {
            $this->miniLog->warning($this->i18n->trans('duplicated-request'));            
            return false;
        }

        $tools = new POSDocumentTools();               
        
        $modelName = $data['tipodocumento'] ?: 'FacturaCliente';
        $className = 'FacturaScripts\\Dinamic\\Model\\' . $modelName;        
        $document = new $className();        

        if ($tools->processDocumentData($document, $data, $this->miniLog)) {
            $this->saveDocumentPayments($document, $data);
            $this->printDocumentTicket($document);

            $this->saveTransaction($document);
        }        
    }

    private function saveDocumentPayments($document, $data)
    {
        $payments = json_decode($data['payments'], true);

        $paymentAmount = $payments['amount'];
        $paymentChange = $payments['change'];

        if ($payments['method'] == AppSettings::get('pointofsale','fpagoefectivo') ) {
            $this->arqueo->saldoesperado += (float) ($paymentAmount - $paymentChange);
            $this->arqueo->save(); 
        }

        /*$newPayment = new PagoCliente();
        $newPayment->cantidad = $paymentAmount - $paymentChange;
        $newPayment->codcliente = $document->codcliente;
        $newPayment->coddivisa = $document->coddivisa;
        $newPayment->codpago = $document->codpago;

        $newPayment->save();*/
    }

    private function saveTransaction($document)
    {
        $transaction = new OperacionPOS();

        $transaction->codigo = $document->codigo;
        $transaction->codcliente = $document->codcliente;
        $transaction->fecha = $document->fecha;
        $transaction->iddocumento = $document->primaryColumnValue();
        $transaction->idsesion = $this->arqueo->idsesion;
        $transaction->tipodoc = $document->modelClassName();
        $transaction->total = $document->total;

        return $transaction->save();
    }

    private function printDocumentTicket($document)
    {
        $businessTicket = new BusinessDocumentTicket($document); 

        $ticket = new Ticket();
        $ticket->coddocument = $document->modelClassName();
        $ticket->text = $businessTicket->getTicket(); 

        if ($ticket->save()) {
            $this->printing = true;
            $msg = '<div class="d-none"><img src="http://localhost:10080?documento=%1s"/></div>';
            $this->miniLog->info('Imprimiendo documento ' . $document->codigo);
            $this->miniLog->info(sprintf($msg,  $document->modelClassName()));
            return true;
        }

         $this->miniLog->warning('Error al imprimir el ticket');
         return false;
    }

    public function getDocColumnsData()
    {
        return POSDocumentOptions::getLineData($this->user);
    }

    public function getDenominaciones()
    {
        return (new DenominacionMoneda)->all([],['valor' => 'ASC']);
    }

    public function getRandomToken()
    {
        return (new MultiRequestProtection)->newToken();
    }

    protected function assets()
    {
        AssetManager::add('css', FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.css');
        AssetManager::add('js', FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.js');
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/PosDocumentView.js');
    }
}
