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
namespace FacturaScripts\Plugins\EasyPOS\Controller;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;

use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\POS as Helpers;

use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\DenominacionMoneda;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\OperacionPOS;
use FacturaScripts\Dinamic\Model\SesionPOS;
use FacturaScripts\Dinamic\Model\TerminalPOS;
use FacturaScripts\Dinamic\Model\Variante;
use function MongoDB\BSON\toJSON;

/**
 * Controller to process Point of Sale Operations
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class POS extends Controller
{
    public $arqueo = false;
    public $cliente;
    public $formaPago;
    public $terminal;    

    /**
     * Returns the cash payment method ID.
     *
     * @return string
     */
    public function getCashPaymentMethod()
    {
        return $this->toolBox()->appSettings()->get('pointofsale', 'fpagoefectivo');
    }

    /**
     * Returns headers and columns available by user permissions.
     *
     * @return string
     */
    public function getDataGridHeaders()
    {
        return Helpers\SalesDataGrid::getDataGridHeaders($this->user);
    }

    /**
     * Returns all available denominations.
     *
     * @return array
     */
    public function getDenominations()
    {
        return (new DenominacionMoneda)->all([], ['valor' => 'ASC']);
    }

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

    /**
     * Returns a random token to avoid multiple form submission.
     *
     * @return string
     */
    public function getRandomToken()
    {
        return $this->multiRequestProtection->newToken();
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \FacturaScripts\Dinamic\Model\User $user
     * @param \FacturaScripts\Core\Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        
        $this->initValues();
        $this->isSessionOpen();

        $this->execAction();
    }

    /**
     * Close current session.
     *
     * @return void
     */
    private function closeSession()
    {
        if (!$this->isSessionOpen()) {
            $this->toolBox()->i18nLog()->info('there-is-no-open-till-session');
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

        $this->toolBox()->i18nLog()->info('cashup-money-counted', ['%amount%' => $total]);
        $this->arqueo->saldocontado = $total;
        $this->arqueo->conteo = json_encode($cash);

        if ($this->arqueo->save()) {
            $this->terminal->disponible = true;
            $this->terminal->save();
            $this->setTemplate('\POS\SessionScreen');
        }

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

            case 'search-customer':
                $this->searchCustomer();
                return false;

            case 'search-product':
                $this->searchProduct();
                return false;

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
        $this->cliente = new Cliente();
        $this->formaPago = new FormaPago();
    }

    /**
     * Verify if a till session is opened by user or pos terminal.
     *
     * @return bool
     */
    private function isSessionOpen()
    {
        $this->arqueo = new SesionPOS();
        $this->terminal = new TerminalPOS();
        $this->setTemplate('\POS\SessionScreen');

        $idterminal = $this->request->query->get('terminal');
        if ($idterminal) {
            $this->terminal->loadFromCode($idterminal);
        }

        if (!$this->arqueo->isOpen('user', $this->user->nick)) {
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
    public function openSession()
    {
        if ($this->isSessionOpen()) {
            $this->toolBox()->log()->info('there-is-an-open-till-session-for-this-user');
            return;
        }

        $idterminal = $this->request->request->get('terminal');
        if (!$this->terminal->loadFromCode($idterminal)) {
            $this->toolBox()->i18nLog()->warning('cash-register-not-found');
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
            $params = [
                '%terminalName%' => $this->terminal->nombre,
                '%userNickname%' => $this->user->nick,
            ];
            $this->toolBox()->i18nLog()->info('till-session-opened', $params);
            $this->toolBox()->i18nLog()->info('cashup-money-counted', ['%amount%' => $saldoinicial]);

            $this->terminal->disponible = false;
            $this->terminal->save();

            $this->setTemplate('\POS\SalesScreen');
            $this->pipe('openSession');
            return;
        }

        $this->arqueo = false;
    }

    /**
     * Recalculate pos document, pos document lines from form data.
     *
     * @return bool
     */
    private function recalculateDocument()
    {
        $this->setTemplate(false);

        $modelName = 'FacturaCliente';

        $documentTools = new Helpers\DocumentTools($modelName);
        $result = $documentTools->recalculateDocument($this->request);

        $this->response->setContent($result);
        return false;
    }

    /**
     * Process sales.
     *
     * @return void
     */
    private function saveDocument()
    {
        $data = $this->request->request->all();

        if (!$this->validateSaveRequest($data)) {
             return;
        }

        $modelName = $data['tipodocumento'] ?: 'FacturaCliente';
        $documentTools = new Helpers\DocumentTools($modelName);

        if ($documentTools->processDocumentData($data)) {
            $document = $documentTools->getDocument();

            $this->saveDocumentPayments($document, $data);
            $this->printDocumentTicket($document);

            $this->saveTransaction($document);
        }
    }

    /**
     * @param $document
     * @param array $data
     */
    private function saveDocumentPayments($document, $data)
    {
        $payments = json_decode($data['payments'], true);

        $paymentAmount = $payments['amount'];
        $paymentChange = $payments['change'];

        if ($payments['method'] == AppSettings::get('pointofsale', 'fpagoefectivo') ) {
            $this->arqueo->saldoesperado += (float) ($paymentAmount - $paymentChange);
            $this->arqueo->save();
        }
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

    private function printCashupTicket()
    {
    }

    private function printDocumentTicket($document)
    {
        $ticket = new Helpers\Tickets($document);
        $this->printing = ($ticket->printTicket()) ? true : false;
    }

    private function validateSaveRequest($data)
    {
        if (!$this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return false;
        }
        $token = $data['token'];
        if (!empty($token) && $this->multiRequestProtection->tokenExist($token)) {
            $this->toolBox()->i18nLog()->warning('duplicated-request');
            return false;
        }
        return true;      
    }

    private function searchProduct()
    {
        //$this->setTemplate(false);
        $this->setTemplate('\POS\Ajax\ProductList');
        $this->response->setContent("Buscando producto..");
    }

    public function searchProductList()
    {
        $query = $this->request->request->get('query');
        $query = str_replace(" ", "%", $query);
        $variante = new Variante();

        return $variante->codeModelSearch($query, "referencia");
    }

    private function searchCustomer()
    {
        //$this->setTemplate(false);
        //$this->response->setContent(print_r($this->searchCustomerList(), true));
        $this->setTemplate('\POS\Ajax\CustomerList');
        $this->response->setContent("Buscando");
    }

    public function searchCustomerList()
    {
        $query = $this->request->request->get('query');
        $cliente = new Cliente();

        return $cliente->codeModelSearch($query);
    }
}
