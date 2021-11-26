<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Model\Serie;
use FacturaScripts\Dinamic\Lib\POS\Printer;
use FacturaScripts\Dinamic\Lib\POS\Sales\Customer;
use FacturaScripts\Dinamic\Lib\POS\Sales\Order;
use FacturaScripts\Dinamic\Lib\POS\Sales\OrderRequest;
use FacturaScripts\Dinamic\Lib\POS\Sales\Product;
use FacturaScripts\Dinamic\Lib\POS\SalesDataGrid;
use FacturaScripts\Dinamic\Lib\POS\SalesSession;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\DenominacionMoneda;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to process Point of Sale Operations
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class POS extends Controller
{
    const DEFAULT_ORDER = 'FacturaCliente';
    const HOLD_ORDER = 'OperacionPausada';

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
     * @var Order
     */
    private $order;

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

        // Get any action that have to be performed
        $action = $this->request->request->get('action', '');

        // Exec action before load all data and stop exceution if not nedeed
        if (false === $this->execPreviusAction($action)) return;

        // Init necesary stuff
        $this->customer = new Cliente();
        $this->formaPago = new FormaPago();
        $this->serie = new Serie();

        // Run operations after load all data
        $this->execAfterAction($action);

        // Set view template
        $template = $this->session->isOpen() ? '\POS\Layout\SalesScreen' : '\POS\Layout\SessionScreen';
        $this->setTemplate($template);
    }

    /**
     * Exec action before load all data.
     *
     * @param string $action
     * @return bool
     */
    protected function execPreviusAction(string $action): bool
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

            case 'resume-order':
                $this->resumeOrder();
                return false;

            case 'recalculate-order':
                $this->recalculateOrder();
                return false;

            case 'delete-order-on-hold':
                $this->deleteOrderOnHold();
                return false;

            case 'save-new-customer':
                $this->saveNewCustomer();
                return false;

            default:
                return true;
        }
    }

    /**
     * Exec action after load all data.
     *
     * @param string $action
     */
    protected function execAfterAction(string $action)
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

            case 'hold-order':
                $this->saveOrderOnHold();
                break;

            case 'print-cashup':
                $this->printClosingVoucher();
                break;

            case 'save-order':
                $this->saveOrder();
                break;

            case 'delete-order-on-hold':
                $this->deleteOrderOnHold();
                break;

            default:
                break;
        }
    }

    /**
     * Search product by barcode.
     */
    protected function searchBarcode()
    {
        $producto = new Product();
        $barcode = $this->request->request->get('query');

        $this->setAjaxResponse($producto->searchBarcode($barcode));
    }

    /**
     * Search customer by text.
     */
    protected function searchCustomer()
    {
        $customer = new Customer();
        $query = $this->request->request->get('query');

        $this->setAjaxResponse($customer->search($query));
    }

    /**
     * Search product by text match on description or code.
     */
    protected function searchProduct()
    {
        $product = new Product();
        $query = $this->request->request->get('query');

        $this->setAjaxResponse($product->search($query));
    }

    /**
     * Set a held order as complete to remove from list.
     */
    protected function deleteOrderOnHold()
    {
        $code = $this->request->request->get('code', '');
        $storage = $this->session->getStorage();

        if ($storage->updateOrderOnHold($code)) {
            $this->toolBox()->i18nLog()->info('pos-order-on-hold-deleted');
            $this->setAjaxResponse('OK');
        }
    }

    /**
     * Put order on hold.
     *
     * @return bool
     */
    protected function saveOrderOnHold(): bool
    {
        if (false === $this->validateOrderRequest()) return false;

        $request = new OrderRequest($this->request, true);
        $order = new Order($request);

        $storage = $this->session->getStorage();

        if ($storage->placeOrderOnHold($order)) {
            $this->toolBox()->i18nLog()->info('pos-order-on-hold');
            return true;
        }

        return false;
    }

    /**
     * Recalculate order data.
     *
     * @return void
     */
    protected function recalculateOrder()
    {
        $request = new OrderRequest($this->request);
        $order = new Order($request);

        $this->setAjaxResponse($order->recalculate(), false);
    }

    /**
     * Load order on hold by code.
     */
    protected function resumeOrder()
    {
        $code = $this->request->request->get('code', '');
        $storage = $this->session->getStorage();

        $this->setAjaxResponse($storage->getOrderOnHold($code));
    }

    /**
     * Save order and payments.
     *
     * @return bool
     */
    protected function saveOrder(): bool
    {
        if (false === $this->validateOrderRequest()) return false;

        $orderRequest = new OrderRequest($this->request);
        $order = new Order($orderRequest);

        $storage = $this->session->getStorage();

        if ($storage->placeOrder($order)) {
            $this->printVoucher($order->getDocument());
            $this->toolBox()->i18nLog()->info('pos-order-ok');

            return true;
        }

        return false;
    }

    /**
     * Close current user POS session.
     */
    protected function closeSession()
    {
        $cash = $this->request->request->get('cash');
        $this->session->close($cash);

        $this->printClosingVoucher();
    }

    /**
     * Print closing voucher.
     *
     * @return void;
     */
    protected function printClosingVoucher()
    {
        $ticketWidth = $this->session->terminal()->anchopapel;
        $message = Printer::cashupTicket($this->session->getArqueo(), $this->empresa, $ticketWidth);

        $this->toolBox()->log()->info($message);
    }

    /**
     * @param $document
     * @return void;
     */
    protected function printVoucher($document)
    {
        $ticketWidth = $this->session->terminal()->anchopapel;
        $message = Printer::salesTicket($document, $ticketWidth);

        $this->toolBox()->log()->info($message);
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function validateOrderRequest(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return false;
        }

        $token = $this->request->request->get('token');

        if (empty($token) || false === $this->multiRequestProtection->validate($token)) {
            $this->toolBox()->i18nLog()->warning('invalid-request');
            return false;
        }

        if ($this->multiRequestProtection->tokenExist($token)) {
            $this->toolBox()->i18nLog()->warning('duplicated-request');
            return false;
        }

        return true;
    }

    /**
     * @param $content
     * @param bool $encode
     */
    protected function setAjaxResponse($content, bool $encode = true): void
    {
        $response = $encode ? json_encode($content) : $content;
        $this->response->setContent($response);
    }

    /**
     * Return POS setting value by given key.
     *
     * @param string $key
     * @return mixed
     */
    protected function getSetting(string $key)
    {
        return $this->toolBox()->appSettings()->get('pointofsale', $key);
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
        return $this->getSetting('fpagoefectivo');
    }

    /**
     * Returns all available payment methods.
     *
     * @return FormaPago[]
     */
    public function availablePaymentMethods(): array
    {
        $formasPago = [];

        $formasPagoCodeList = explode('|', $this->getSetting('formaspago'));
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

    private function saveNewCustomer()
    {
        $customer = new Customer();

        $taxID = $this->request->request->get('taxID');
        $name = $this->request->request->get('name');

        if ($customer->saveNew($taxID, $name)) {
            $this->setAjaxResponse($customer->getCustomer());
            return;
        }

        $this->setAjaxResponse('Error al guardar el cliente');
    }
}
