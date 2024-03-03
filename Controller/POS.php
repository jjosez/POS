<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleActionsTrait;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleCustomer;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleProduct;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleRequest;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleSession;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleTrait;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleTransaction;
use FacturaScripts\Plugins\POS\Model\MovimientoPuntoVenta;
use Symfony\Component\HttpFoundation\Response;

class POS extends Controller
{
    use PointOfSaleTrait;
    use PointOfSaleActionsTrait;

    const DEFAULT_POS_DOCUMENT = 'FacturaCliente';
    const PAUSED_POS_DOCUMENT = 'OperacionPausada';

    /**
     * @var string
     */
    protected $token;

    /**
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     * @throws KernelException
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate(false);
        $action = $this->request->request->get('action', '');

        if ($action && true === $this->execCartQueryAction($action)) {
            return;
        }

        $this->session = new PointOfSaleSession($user);

        if ($action && false === $this->execAction($action)) {
            return;
        }

        $this->execAfterAction($action);
        $this->loadCustomDocumentFields();
        $this->loadCustomMenuElements();

        $template = $this->session->getView();
        $this->setTemplate($template);
    }

        protected function execAction(string $action): bool
        {
        switch ($action) {
            case 'search-barcode':
                $this->searchBarcode();
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

            case 'hold-order':
                $this->saveOrderOnHold();
                $this->buildResponse();
                return false;

            case 'save-order':
                $this->saveOrder();
                $this->buildResponse(['last-order' => $this->session->getLastOrder()->idoperacion]);
                return false;

            case 'get-orders-on-hold':
                $this->setResponse(self::getPausedDocuments());
                return false;

            case 'get-last-orders':
                $result = self::getSessionOrders($this->getSession()->getID());
                $this->setResponse($result);
                return false;

            case 'print-closing-voucher':
                $this->printClosingVoucher();
                $this->buildResponse();
                return false;

            case 'set-family-filter':
                $this->setFamilyFilter();
                return false;

            case 'print-desktop-ticket':
                $this->printOrder();
                return false;

            case 'reprint-paused-order':
                $this->printPausedDocument();
                return false;

            case 'print-mobile-ticket':
                $this->printOrderFromMobile();
                return false;

            case 'print-mobile-paused-ticket':
                $this->printPausedTicketFromMobile();
                return false;

            default:
                $this->setResponse('Funcion no encontrada');
                return true;
        }
    }

    protected function execCartQueryAction(string $action): bool
    {
        switch ($action) {
            case 'delete-order-on-hold':
                $this->deleteOrderOnHold();
                return true;

            case 'recalculate-order':
                $this->recalculateOrder();
                return true;

            case 'resume-order':
                $this->resumeOrder();
                return true;

            case 'save-new-customer':
                $this->saveNewCustomer();
                return true;

            case 'search-customer':
                $this->searchCustomer();
                return true;

            default:
                $this->setResponse('Funcion no encontrada');
                return false;
        }
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

    /**
     * Remove paused order from list.
     */
    protected function deleteOrderOnHold()
    {
        if (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');

            $this->buildResponse();
            return;
        }

        $code = $this->request->request->get('code', '');

        if (self::deletePausedDocument($code)) {
            Tools::log()->info('pos-order-on-hold-deleted');
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
        $transaction = new PointOfSaleTransaction($request);

        $this->setResponse($transaction->recalculate());
    }

    /**
     * Load order on hold by code.
     */
    protected function resumeOrder()
    {
        $code = $this->request->request->get('code', '');
        $document = self::getPausedDocument($code);

        $result = ['doc' => $document, 'lines' => $document->getLines()];

        $this->setNewToken();
        $this->buildResponse($result);
    }

    /**
     * Save money In and Out movments
     *
     * @return void
     */
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
            Tools::log()->info('money-movment-ok');
        }

        $this->buildResponse();
    }

    protected function saveNewCustomer()
    {
        $customer = new PointOfSaleCustomer();

        $taxID = $this->request->request->get('taxID');
        $name = $this->request->request->get('name');
        $result = [];

        if ($customer->saveNew($taxID, $name)) {
            Tools::log()->info('Nuevo cliente registrado');
            $result = ['customer' => $customer->getCustomer()];
            //$this->setResponse($customer->getCustomer());
        }

        $this->buildResponse($result);
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
     * Search product by barcode.
     */
    protected function searchBarcode()
    {
        $barcode = $this->request->request->get('query');

        $this->setResponse(PointOfSaleProduct::searchBarcode($barcode));
    }

    /**
     * Search product by text.
     */
    protected function searchProduct()
    {
        $query = $this->request->request->get('query', '');

        $source = $this->getTerminal()->productsource;
        $company = '';
        $warehouse = '';

        if ($source) {
            switch ($source) {
                case $this->getTerminal()::PRODUCTS_FROM_COMPANY:
                    $company = $this->getTerminal()->idempresa;
                    break;
                case $this->getTerminal()::PRODUCTS_FROM_WAREHOUSE:
                    $warehouse = $this->getTerminal()->codalmacen;
            }
        }

        $this->setResponse(PointOfSaleProduct::search($query, [], $warehouse, $company));
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
     * Put order on hold.
     *
     * @return void
     */
    protected function saveOrderOnHold(): void
    {
        if (false === $this->validateRequest()) return;

        $documentType = $this->request->get('tipo-documento', self::DEFAULT_POS_DOCUMENT);
        $this->request->request->set('generadocumento', $documentType);
        $this->request->request->set('tipo-documento', self::PAUSED_POS_DOCUMENT);

        $request = new PointOfSaleRequest($this->request);
        $transaction = new PointOfSaleTransaction($request);

        $this->dataBase->beginTransaction();

        if (false === $transaction->saveDocument()) {
            Tools::log()->warning('pos-order-on-hold-error');
            $this->dataBase->rollback();
            return;
        }

        $this->dataBase->commit();
        Tools::log()->info('pos-order-on-hold');
    }

    /**
     * Save order and payments.
     *
     * @return void
     */
    protected function saveOrder(): void
    {
        if (false === $this->validateRequest()) return;

        $request = new PointOfSaleRequest($this->request);
        $transaction = new PointOfSaleTransaction($request);

        if ($this->pipeFalse('saveRequest', $this->request) === false) {
            return;
        }

        $this->dataBase->beginTransaction();

        if (false === $transaction->saveDocument()) {
            $this->dataBase->rollback();
            return;
        }

        $document = $transaction->getDocument();
        if (false === $this->getSession()->saveOrder($document)) {
            $this->dataBase->rollback();
            return;
        }

        if (isset($document->idpausada) && false === self::completePausedDocument($document->idpausada)) {
            $this->dataBase->rollback();
            return;
        }


        $this->dataBase->commit();

        $this->getSession()->savePayments($document, $transaction->getPayments());
        $this->pipe('save', $document, $transaction->getPayments());
        //$this->printVoucher($document, $transaction->getPayments());
    }

    /**
     * Reprint order by code.
     */
    protected function printOrder()
    {
        $code = $this->request->request->get('code', '');

        if ($code) {
            $order = self::getOrder($code);
            $this->printVoucher($order->getDocument(), []);

            $this->buildResponse();
        }
    }

    /**
     * Reprint order by code.
     */
    protected function printPausedDocument()
    {
        $code = $this->request->request->get('code', '');

        if ($code) {
            $document = self::getPausedDocument($code);
            $this->printVoucher($document, []);

            $this->buildResponse();
        }
    }

    /**
     * Reprint order by code.
     */
    protected function printOrderFromMobile()
    {
        $code = $this->request->request->get('code', '');

        if ($code) {
            $order = self::getOrder($code);
            $ticket = $this->printVoucherMobile($order->getDocument(), []);

            $this->setResponse($ticket, false);
        }
    }

    /**
     * Reprint order by code.
     */
    protected function printPausedTicketFromMobile()
    {
        $code = $this->request->request->get('code', '');

        if ($code) {
            $document = self::getPausedDocument($code);
            $ticket = $this->printVoucherMobile($document, []);

            $this->setResponse($ticket, false);
        }
    }

    protected function execAfterAction(string $action)
    {
        switch ($action) {
            case 'change-user':
                $this->changeUser();
                break;
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

    protected function changeUser(): void
    {
        /*$user = new User();
        $nick = $this->request->request->get('userNick', '');
        $password = $this->request->request->get('userPassword', '');

        if ($nick === '' || $password === '') {
            return;
        }

        if ($user->loadFromCode($nick) && $user->enabled) {
            if ($user->verifyPassword($password)) {
                $user->newLogkey($this->user->lastip, $this->user->lastbrowser);
                $user->save();
                $this->session->updateUser($user);

                $expire = time() + FS_COOKIES_EXPIRE;
                $this->response->headers->setCookie(new Cookie('fsNick', $user->nick, $expire, FS_ROUTE));
                $this->response->headers->setCookie(new Cookie('fsLogkey', $user->logkey, $expire, FS_ROUTE));
                $this->response->headers->setCookie(new Cookie('fsLang', $user->langcode, $expire, FS_ROUTE));
                $this->response->headers->setCookie(new Cookie('fsCompany', $user->idempresa, $expire, FS_ROUTE));

                $this->toolBox()->i18nLog()->info('login-ok', ['%nick%' => $user->nick]);
                header("Refresh:0");
                return;
            }

            $ipFilter = $this->toolBox()->ipFilter();
            $ipFilter->setAttempt($this->user->lastip);

            $this->toolBox()->i18nLog()->warning('login-password-fail');
        }*/
    }

    protected function initSession()
    {
        $terminal = $this->request->request->get('terminal', '');
        $amount = $this->request->request->get('saldoinicial', 0) ?: 0;
        $this->session->openSession($terminal, $amount);
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
     * Close current user POS session.
     */
    protected function closeSession()
    {
        $cash = $this->request->request->get('cash');

        if ($this->session->closeSession($cash)) {
            $this->printClosingVoucher();
        }
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
