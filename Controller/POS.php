<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Model\Serie;
use FacturaScripts\Dinamic\Lib\POS\PrintProcessor;
use FacturaScripts\Dinamic\Lib\POS\SalesDataGrid;
use FacturaScripts\Dinamic\Lib\POS\SalesSession;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\DenominacionMoneda;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\POS\Lib\POS\Sales\Customer;
use FacturaScripts\Plugins\POS\Lib\POS\Sales\Product;
use FacturaScripts\Plugins\POS\Lib\POS\Sales\Order;
use FacturaScripts\Plugins\POS\Lib\POS\Sales\OrderRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to process Point of Sale Operations
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class POS extends Controller
{
    const DEFAULT_TRANSACTION = 'FacturaCliente';
    const PAUSED_TRANSACTION = 'OperacionPausada';

    /**
     * @var Cliente
     */
    public $customer;

    /**
     * @var FormaPago
     */
    public $formaPago;

    /**
     * @var SalesSession
     */
    public $session;


    /**
     * @var Serie
     */
    public $serie;

    /**
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate(false);

        // Init till session
        $this->session = new SalesSession($this->user);

        // Get any operations that have to be performed
        $action = $this->request->request->get('action', '');

        // Run operations before load all data and stop exceution if not nedeed
        if (false === $this->execPreviusAction($action)) return;

        // Init necesary stuff
        $this->customer = new Cliente();
        $this->formaPago = new FormaPago();
        $this->serie = new Serie();

        // Run operations after load all data
        $this->execAfterAction($action);

        // Set view template
        $template = $this->session->isOpen() ? '\POS\SalesScreen' : '\POS\SessionScreen';
        $this->setTemplate($template);
    }

    /**
     * @param string $action
     * @return bool
     */
    private function execPreviusAction(string $action): bool
    {
        switch ($action) {
            case 'search-barcode':
                $this->searchBarcode();
                return false;

            case 'search-customer':
                $this->searchCustomer();
                return false;

            case 'search-product':
                $this->searchProduct();
                return false;

            case 'transaction-resume':
                $this->resumeTransaction();
                return false;

            case 'transaction-recalculate':
                $this->recalculateTransaction();
                return false;

            default:
                return true;
        }
    }

    protected function searchBarcode()
    {
        $producto = new Product();
        $barcode = $this->request->request->get('query');

        $this->response->setContent($producto->searchBarcode($barcode));
    }

    protected function searchCustomer()
    {
        $customer = new Customer();
        $query = $this->request->request->get('query');

        $this->response->setContent($customer->searchCustomer($query));
    }

    protected function searchProduct()
    {
        $product = new Product();
        $query = $this->request->request->get('query');

        $this->response->setContent($product->searchByText($query));
    }

    /**
     * Load a paused document
     */
    protected function resumeTransaction()
    {
        $code = $this->request->request->get('code', '');
        $result = $this->session->loadPausedTransaction($code);

        $this->response->setContent($result);
    }

    protected function recalculateTransaction()
    {
        $request = new OrderRequest($this->request);
        $transaction = new Order($request);

        $result = $transaction->recalculate();

        $this->response->setContent($result);
    }

    /**
     * Exect action before load data.
     *
     * @param string $action
     */
    private function execAfterAction(string $action)
    {
        switch ($action) {
            case 'close-session':
                $this->closeSession();
                break;

            case 'open-session':
                $idterminal = $this->request->request->get('terminal', '');
                $amount = $this->request->request->get('saldoinicial', 0);
                $this->session->open($idterminal, $amount);
                break;

            case 'open-terminal':
                $idterminal = $this->request->request->get('terminal', '');
                $this->session->terminal($idterminal);
                break;

            case 'pause-document':
                $this->holdTransaction();
                break;

            case 'print-cashup':
                $this->printCashup();
                break;

            case 'save-document':
                $this->saveOrder();
                break;

            case 'delete-paused-document':
                $this->deletePausedTransaction();
                break;

            default:
                break;
        }
    }

    private function closeSession()
    {
        $cash = $this->request->request->get('cash');
        $this->session->close($cash);

        $this->printCashup();
    }

    /**
     * @return void;
     */
    protected function printCashup()
    {
        $ticketWidth = $this->session->terminal()->anchopapel;
        if (PrintProcessor::printCashup($this->session->getArqueo(), $this->empresa, $ticketWidth)) {
            $values = [
                '%ticket%' => 'Cierre caja',
                '%code%' => 'cashup'
            ];
            $this->toolBox()->i18nLog()->info('printing-ticket', $values);
            return;
        }

        $this->toolBox()->i18nLog()->warning('error-printing-ticket');
    }

    /**
     * Process sales.
     *
     * @return void
     */
    private function holdTransaction()
    {
        if (false === $this->validateSaveRequest($this->request)) return;

        $this->request->request->set('tipo-documento', self::PAUSED_TRANSACTION);
        $request = new OrderRequest($this->request);
        $transaction = new Order($request);

        if ($transaction->hold()) {
            $this->toolBox()->i18nLog()->info('operation-is-paused');
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function validateSaveRequest(Request $request): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return false;
        }

        $token = $request->request->get('token');

        if (!empty($token) && $this->multiRequestProtection->tokenExist($token)) {
            $this->toolBox()->i18nLog()->warning('duplicated-request');
            return false;
        }
        return true;
    }

    /**
     * Process sales.
     *
     * @return void
     */
    protected function saveOrder()
    {
        if (false === $this->validateSaveRequest($this->request)) return;

        $orderRequest = new OrderRequest($this->request);
        $order = new Order($orderRequest);

        if ($order->save()) {
            $this->session->storeTransaction($order);
            $this->printTicket($order->getDocument());
        }
    }

    /**
     * @param $document
     * @return void;
     */
    protected function printTicket($document)
    {
        $ticketWidth = $this->session->terminal()->anchopapel;
        if (PrintProcessor::printDocument($document, $ticketWidth)) {
            $values = [
                '%ticket%' => $document->codigo,
                '%code%' => $document->modelClassName()
            ];
            $this->toolBox()->i18nLog()->info('printing-ticket', $values);
            return;
        }

        $this->toolBox()->i18nLog()->warning('error-printing-ticket');
    }

    /**
     * Set a paused document as complete to remove from list
     */
    protected function deletePausedTransaction()
    {
        $code = $this->request->request->get('idpausada', '');
        $this->session->updatePausedTransaction($code);
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
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
    public function cashPaymentMethod(): ?string
    {
        return $this->getSettingValue('fpagoefectivo');
    }

    protected function getSettingValue(string $key)
    {
        return $this->toolBox()->appSettings()->get('pointofsale', $key);
    }

    /**
     * Returns all available payment methods.
     *
     * @return FormaPago[]
     */
    public function availablePaymentMethods(): array
    {
        $formasPago = [];

        $formasPagoCodeList = explode('|', $this->getSettingValue('formaspago'));
        foreach ($formasPagoCodeList as $value) {
            $formasPago[] = (new FormaPago())->get($value);
        }

        return $formasPago;
    }

    /**
     * Returns headers and columns available by user permissions.
     *
     * @return array
     */
    public function getGridHeaders(): array
    {
        return SalesDataGrid::getDataGrid($this->user);
    }

    /**
     * Returns all available denominations.
     *
     * @return array
     */
    public function getDenominations(): array
    {
        return (new DenominacionMoneda())->all([], ['valor' => 'ASC']);
    }

    /**
     * Returns a random token to use as transaction id.
     *
     * @return string
     */
    public function requestToken(): string
    {
        return $this->multiRequestProtection->newToken();
    }

    public function customFieldList(): array
    {
        $path = FS_FOLDER . '/Dinamic/View/POS/Block/CustomField/';
        $list = scandir($path);

        if (false !== $list) {
            return array_diff($list, array('..', '.'));
        }

        return [];
    }
}
