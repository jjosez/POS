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
use FacturaScripts\Plugins\POS\Model\MovimientoPuntoVenta;
use Symfony\Component\HttpFoundation\Response;

class POS extends Controller
{
    use PointOfSaleTrait;

    /**
     * @var string
     */
    protected $token;

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

            case 'cash-movment':
                $this->saveMovments();
                return true;

            case 'get-product-stock':
                $this->searchStock();
                return false;

            case 'get-product-images':
                $id = $this->request->request->get('id', '');
                $code = $this->request->request->get('code', '');

                $this->setResponse($this->getProductImageList($id, $code));
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
                $this->setResponse($this->getSession()->getPausedOrders());
                return false;

            case 'get-last-orders':
                $this->setResponse($this->getSession()->getOrders());
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

        $source = $this->getTerminal()->productsource;
        $company = '';
        $warehouse = '';

        if ($source) {
            switch ($source) {
                case 1:
                    $company = $this->getTerminal()->idempresa;
                    break;
                case 2:
                    $warehouse = $this->getTerminal()->codalmacen;
            }
        }

        $this->setResponse($product->search($query, [], $warehouse, $company));
    }

    /**
     * Search product by text.
     */
    protected function searchStock()
    {
        $product = new PointOfSaleProduct();
        $query = $this->request->request->get('query', '');

        $this->setResponse($product->getStock($query));
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

        if ($this->getSession()->deletePausedOrder($code)) {
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
        $order = $this->getSession()->getPausedOrder($code);

        $result = ['doc' => $order->toArray(), 'lines' => $order->getLines()];

        $this->setNewToken();
        $this->buildResponse($result);
    }

    /**
     * Reprint order by code.
     */
    protected function reprintOrder()
    {
        $code = $this->request->request->get('code', '');

        if ($code) {
            $order = $this->getSession()->getOrder($code);
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
            return;
        }

        $this->buildResponse();
    }

    protected function saveMovments()
    {
        if (false === $this->validateRequest()) return;

        $amount = $this->request->request->get('amount');
        $description = $this->request->request->get('description');

        $movment = new MovimientoPuntoVenta();
        $movment->idsesion = $this->session->getSession()->idsesion;
        $movment->nickusuario = $this->user->nick;
        $movment->descripcion = $description;
        $movment->total = $amount ?? 0;

        if ($movment->save()) {
            $this->session->updateCashAmount($movment->total);
            self::toolBox()::log()->info('Movimiento cuardado correctamente.');
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

        $saleRequest = new PointOfSaleRequest($this->request);
        $saleOrder = new PointOfSaleOrder($saleRequest);

        $this->dataBase->beginTransaction();

        if (false === $saleOrder->saveDocument()) {
            $this->dataBase->rollback();
            $this->buildResponse();
            return;
        }

        if (false === $this->getSession()->saveOrder($saleOrder->getDocument())) {
            $this->dataBase->rollback();
            $this->buildResponse();
            return;
        }

        $this->dataBase->commit();

        $this->savePayments($saleRequest->getPaymentList(), $this->getSession()->getLastOrder());
        $this->printVoucher($saleOrder->getDocument());

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

        if (false === $order->saveDocument()) {
            $this->toolBox()->i18nLog()->warning('pos-order-on-hold-error');
            $this->dataBase->rollback();
            return;
        }

        $this->dataBase->commit();

        $this->toolBox()->i18nLog()->info('pos-order-on-hold');
        $this->buildResponse();
    }

    protected function savePayments(array $payments, $order)
    {
        $cashAmount = PointOfSalePayments::saveOrderPayments($this->getCashPaymentMethod(), $order, $payments);

        $this->getSession()->updateCashAmount($cashAmount);
    }

    /**
     * @return void
     */
    protected function loadTerminal()
    {
        $id = $this->request->request->get('terminal', '');
        $this->session->getTerminal($id);
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
