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

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Lib\POS\SalesDataGrid;
use FacturaScripts\Dinamic\Lib\POS\SessionManager;
use FacturaScripts\Dinamic\Lib\POS\SalesProcessor;

use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\DenominacionMoneda;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Dinamic\Model\Variante;
use function json_encode;

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
    public $session;

    /**
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate(false);

        /** Init till session */
        $this->session = new SessionManager($this->user);

        // Get any operations that have to be performed
        $action = $this->request->request->get('action', '');

        /** Run operations before load all data and stop exceution if not nedeed*/
        if ($this->execPreviusAction($action) === false) return;

        /** Init necesary stuff*/
        $this->cliente = new Cliente();
        $this->formaPago = new FormaPago();
        $this->terminal = $this->session->getTerminal();

        /** Run operations after load all data */
        $this->execAfterAction($action);

        /** Set view template*/
        $template = $this->session->isOpen() ? '\POS\SalesScreen' : '\POS\SessionScreen';
        $this->setTemplate($template);
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
        return SalesDataGrid::getDataGridHeaders($this->user);
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
     * Returns a random token to avoid multiple form submission.
     *
     * @return string
     */
    public function getRandomToken()
    {
        return $this->multiRequestProtection->newToken();
    }

    /**
     * Exect action before load data.
     *
     * @param string $action
     */
    private function execAfterAction(string $action)
    {
        switch ($action) {
            case 'open-session':
                $idterminal = $this->request->request->get('terminal', '');
                $amount = $this->request->request->get('saldoinicial', 0);
                $this->session->openSession($idterminal, $amount);
                break;

            case 'open-terminal':
                $idterminal = $this->request->request->get('terminal', '');
                $this->terminal = $this->session->getTerminal($idterminal);
                break;

            case 'close-session':
                $cash = $this->request->request->get('cash');
                $this->session->closeSession($cash);
                break;

            case 'save-document':
                $this->processDocument();
                break;

            default:
                break;
        }
    }

    /**
     * @param string $action
     * @return bool
     */
    private function execPreviusAction(string $action)
    {
        switch ($action) {
            case 'custom-search':
                $this->customSearch();
                return false;

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
                return true;
        }
    }

    /**
     * Process sales.
     *
     * @return void
     */
    private function processDocument()
    {
        $data = $this->request->request->all();
        $modelName = $data['tipo-documento'];

        if (!$this->validateSaveRequest($data)) return;

        $salesProcessor = new SalesProcessor($modelName, $data);
        if ($salesProcessor->saveDocument()){
            $this->saveSessionOperation($salesProcessor->getDocument());
            $this->printTicket($salesProcessor->getDocument());
        }
    }

    private function recalculateDocument()
    {
        $data = $this->request->request->all();
        $modelName = 'FacturaCliente';

        $salesProcessor = new SalesProcessor($modelName, $data);
        $result = $salesProcessor->recalculateDocument();

        $this->response->setContent($result);
    }

    private function searchCustomer()
    {
        $query = $this->request->request->get('query');
        $cliente = new Cliente();

        $result = $cliente->codeModelSearch($query);
        $this->response->setContent(json_encode($result));
    }

    private function searchProduct()
    {
        $query = $this->request->request->get('query');
        $query = str_replace(" ", "%", $query);
        $variante = new Variante();

        $result = $variante->codeModelSearch($query, "referencia");
        $this->response->setContent(json_encode($result));
    }

    private function testExecutionTime()
    {
        return microtime(true);
    }

    /**
     * @param $data
     * @return bool
     */
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

    private function printTicket($document)
    {
        $this->toolBox()->i18nLog()->info('printing');
    }

    private function saveSessionOperation(BusinessDocument $getDocument)
    {
    }

    private function customSearch()
    {
        $query = $this->request->request->get('query');
        $target = $this->request->request->get('target');

        switch ($target){
            case 'customer':
                $result = (new Cliente())->codeModelSearch($query);
                break;

            case 'product':
                $query = str_replace(" ", "%", $query);
                $result = (new Variante())->codeModelSearch($query, 'referencia');
                break;
        }
        $this->response->setContent(json_encode($result));
    }
}
