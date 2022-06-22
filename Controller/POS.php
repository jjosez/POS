<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleCustomer;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleOrder;
use FacturaScripts\Plugins\POS\Lib\PointOfSalePayments;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleProduct;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleRequest;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleSession;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleTrait;
use Symfony\Component\HttpFoundation\Response;

class POS extends Controller
{
    use PointOfSaleTrait;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var PointOfSaleOrder
     */
    protected $lastOrder;

    /**
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate(false);

        $this->session = new PointOfSaleSession($user);
        $action = $this->request->request->get('action', '');

        if ($action && false === $this->execAction($action)) {
            return;
        }

        $this->execAfterAction($action);

        $template = $this->session->getView();
        $this->setTemplate($template);
    }

    protected function execAction(string $action): bool
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

            case 'recalculate-order':
                $this->recalculateOrder();
                return false;

            case 'save-new-customer':
                $this->saveNewCustomer();
                return false;

            case 'hold-order':
                $this->saveOrderOnHold();
                return false;

            case 'save-order':
                $this->saveOrder();
                return false;

            case 'get-orders-on-hold':
                $this->setResponse($this->getStorage()->getOrdersOnHold());
                return false;

            case 'get-last-orders':
                $this->setResponse($this->getStorage()->getLastOrders());
                return false;

            case 'delete-order-on-hold':
                $this->deleteOrderOnHold();
                return false;

            case 'resume-order':
                $this->resumeOrder();
                return false;

            case 'reprint-order':
                $this->reprintOrder();
                return false;
            case 'print-closing-voucher':
                $this->printClosingVoucher();
                $this->buildResponse();
                return false;

            default:
                $this->setResponse('Funcion no encontrada');
                return true;
        }
    }

    protected function execAfterAction(string $action)
    {
        switch ($action) {
            case 'open-session':
                $this->initSession();
                break;
            case 'open-terminal':
                $this->loadTerminal();
                break;
            case 'close-session':
                $this->closeSession();
                break;
        }
    }

    /**
     * Search product by barcode.
     */
    protected function searchBarcode()
    {
        $producto = new PointOfSaleProduct();
        $barcode = $this->request->request->get('query');

        $this->setResponse($producto->searchBarcode($barcode));
    }

    /**
     * Search customer by text.
     */
    protected function searchCustomer()
    {
        $customer = new PointOfSaleCustomer();
        $query = $this->request->request->get('query');

        $this->setResponse($customer->search($query));
    }

    /**
     * Search product by text.
     */
    protected function searchProduct()
    {
        $product = new PointOfSaleProduct();
        $query = $this->request->request->get('query', '');
        //$tags = $this->request->request->get('tags', []);

        $this->setResponse($product->advancedSearch($query, [], $this->getTerminal()->codalmacen));
    }

    /**
     * @param array $data
     * @return void
     */
    protected function buildResponse(array $data = [])
    {
        $response = $data;
        $response['messages'] = $this->getMessages();
        $response['token'] = $this->token;

        $this->setResponse($response);
    }

    protected function initSession()
    {
        $terminal = $this->request->request->get('terminal', '');
        $amount = $this->request->request->get('saldoinicial', 0) ?: 0;
        $this->session->openSession($terminal, $amount);
    }

    /**
     * Close current user POS session.
     */
    protected function closeSession()
    {
        $cash = $this->request->request->get('cash');
        $this->session->closeSession($cash);

        $this->printClosingVoucher();
    }

    /**
     * Set a held order as complete to remove from list.
     */
    protected function deleteOrderOnHold()
    {
        $code = $this->request->request->get('code', '');

        if ($this->getStorage()->updateOrderOnHold($code)) {
            $this->toolBox()->i18nLog()->info('pos-order-on-hold-deleted');
        }

        $this->setNewToken();
        $this->buildResponse();
    }

    /**
     * Recalculate order data.
     *
     * @return void
     */
    protected function recalculateOrder()
    {
        $request = new PointOfSaleRequest($this->request);
        $order = new PointOfSaleOrder($request);

        $this->setResponse($order->recalculate());
    }

    /**
     * Load order on hold by code.
     */
    protected function resumeOrder()
    {
        $code = $this->request->request->get('code', '');

        $this->setNewToken();
        $this->buildResponse($this->getStorage()->getOrderOnHold($code));
    }

    /**
     * Reprint order by code.
     */
    protected function reprintOrder()
    {
        $code = $this->request->request->get('code', '');

        if ($code) {
            $order = $this->getStorage()->getOrder($code);
            $this->printVoucher($order->getDocument());
            $this->buildResponse();
        }
    }

    protected function saveNewCustomer()
    {
        $customer = new PointOfSaleCustomer();

        $taxID = $this->request->request->get('taxID');
        $name = $this->request->request->get('name');

        if ($customer->saveNew($taxID, $name)) {
            $this->setResponse($customer->getCustomer());
        }

        $this->buildResponse();
    }

    /**
     * Save order and payments.
     *
     * @return void
     */
    protected function saveOrder(): void
    {
        if (false === $this->validateRequest()) return;

        $orderRequest = new PointOfSaleRequest($this->request);
        $order = new PointOfSaleOrder($orderRequest);

        $this->dataBase->beginTransaction();

        if (false === $this->getStorage()->saveOrder($order)) {
            $this->dataBase->rollback();
            $this->buildResponse();
            return;
        }

        $this->dataBase->commit();

        $this->savePayments($order->getPayments());
        $this->printVoucher($order->getDocument());
        $this->lastOrder = $order;
        $this->buildResponse();
    }

    /**
     * Put order on hold.
     *
     * @return void
     */
    protected function saveOrderOnHold(): void
    {
        if (false === $this->validateRequest()) return;

        $request = new PointOfSaleRequest($this->request, true);
        $order = new PointOfSaleOrder($request);

        $this->dataBase->beginTransaction();

        if (false === $order->save()) {
            $this->toolBox()->i18nLog()->warning('pos-order-on-hold-error');
            $this->dataBase->rollback();
            return;
        }

        $this->dataBase->commit();

        $this->toolBox()->i18nLog()->info('pos-order-on-hold');
        $this->buildResponse();
    }

    protected function savePayments(array $payments)
    {
        $order = $this->getStorage()->getCurrentOrder();

        $storage = new PointOfSalePayments($order, $this->getCashPaymentMethod());
        $storage->savePayments($payments);

        $this->session->getSession()->saldoesperado += $storage->getCashPaymentAmount();
        $this->session->getSession()->save();
    }

    /**
     * @return void
     */
    protected function loadTerminal()
    {
        $id = $this->request->request->get('terminal', '');
        $this->getSession()->getTerminal($id);
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'POS';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-shopping-cart';
        $pagedata['showonmenu'] = true;

        return $pagedata;
    }
}
