<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use Exception;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleCustomer;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleProduct;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleRequest;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleSession;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleStorage;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleTrait;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleTransaction;

class POS extends Controller
{
    use PointOfSaleTrait;

    const DEFAULT_POS_DOCUMENT = 'FacturaCliente';
    const DRAFT_POS_DOCUMENT = 'BorradorPuntoVenta';

    /**
     * @var string
     */
    protected $token;

    /**
     * @var array
     */
    protected $responseData = [];

    /**
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     * @throws KernelException
     * @throws Exception
     */
    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate(false);
        $action = $this->request->get('action', '');

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
        $this->loadPointOfSaleHooks();

        $template = $this->session->getView();
        $this->setTemplate($template);
    }

    /**
     * @throws Exception
     */
    protected function execAction(string $action): bool
    {
        switch ($action) {
            case 'search-barcode':
                $this->searchBarcode();
                return false;

            case 'cash-entry-action':
                $this->saveCashEntry();
                return true;

            case 'cash-withdraw-action':
                $this->saveCashWithdraw();
                return true;

            case 'get-product-stock':
                $this->searchStock();
                return false;

            case 'get-product-images':
                $id = $this->request->request->get('id', '');
                $code = $this->request->request->get('code', '');

                $this->setResponse(PointOfSaleProduct::getImagesUrl($id, $code));
                return false;

            case 'save-draft':
                $this->saveDraft();
                $this->buildResponse();
                return false;

            case 'save-order':
                $this->saveOrder();
                $this->buildResponse();
                return false;

            case 'get-orders-on-hold':
                $this->setResponse(PointOfSaleStorage::getDraftDocuments());
                return false;

            case 'get-last-orders':
                $result = PointOfSaleStorage::getOrders(PointOfSaleSession::getSessionID());
                $this->setResponse($result);
                return false;

            case 'print-x-report':
                $this->printCashRegisterReportX();
                $this->buildResponse();
                return false;

            case 'set-family-filter':
                $this->setFamilyFilter();
                return false;

            case 'print-draft-ticket':
                $this->printDraftTicket();
                return false;

            case 'print-sales-ticket':
                $this->printOrderTicket();
                return false;

            case 'close-session':
                $this->closeSession();
                return false;

            default:
                $this->setResponse('not-found-action');
                return true;
        }
    }

    protected function execAfterAction(string $action): void
    {
        switch ($action) {
            case 'change-user':
                $this->changeUser();
                break;
            case 'open-session':
                $this->openSession();
                break;
            case 'open-terminal':
                $this->openTerminal();
                break;
        }
    }

    /**
     * Execute Cart specific actions.
     *
     * @param string $action
     * @return bool
     */
    protected function execCartQueryAction(string $action): bool
    {
        switch ($action) {
            case 'delete-order-on-hold':
                $this->deleteDraftOrder();
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

            case 'search-product':
                $this->searchProduct();
                return true;

            default:
                $this->setResponse('not-found-action');
                return false;
        }
    }

    /**
     * @param array $data
     * @return void
     */
    protected function buildResponse(array $data = []): void
    {
        $response = array_merge($data, $this->responseData);

        $response['messages'] = $this->getMessages();
        $response['token'] = $this->token;

        $this->setResponse($response);
    }

    /**
     * Remove paused order from a list.
     */
    protected function deleteDraftOrder(): void
    {
        if (false === self::validateDelete()) {
            $this->buildResponse();

            return;
        }

        $code = $this->request->request->get('code', '');

        if (PointOfSaleStorage::deleteDraftDocument($code)) {
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
    protected function recalculateOrder(): void
    {
        $request = new PointOfSaleRequest($this->request);
        $transaction = new PointOfSaleTransaction($request);

        $this->setResponse($transaction->recalculate());
    }

    /**
     * Load order on hold by code.
     */
    protected function resumeOrder(): void
    {
        $code = $this->request->request->get('code', '');

        if ($code) {
            $document = PointOfSaleStorage::getDraftDocument($code);

            $result = ['doc' => $document, 'lines' => $document->getLines()];

            $this->setNewToken();
            $this->buildResponse($result);
        }
    }

    protected function saveCashEntry(): void
    {
        if (false === $this->validateRequest()) return;

        $amount = $this->request->request->get('amount', 0);
        $description = $this->request->request->get('description');

        if (!is_numeric($amount) || $amount <= 0) {
            Tools::log()->error('invalid-amount');
            return;
        }

        if (PointOfSaleStorage::saveCashMovment($amount, $description)) {
            Tools::log()->notice('cash-entry-ok');
        }

        $this->buildResponse();
    }

    protected function saveCashWithdraw(): void
    {
        if (false === $this->validateRequest()) return;

        $amount = $this->request->request->get('amount', 0);
        $description = $this->request->request->get('description');

        if (!is_numeric($amount) || $amount <= 0) {
            Tools::log()->error('invalid-amount');
            return;
        }

        $amount *= -1;
        if (PointOfSaleStorage::saveCashMovment($amount, $description)) {
            Tools::log()->notice('cash-withdraw-ok');
        }

        $this->buildResponse();
    }

    protected function saveNewCustomer(): void
    {
        $customer = new PointOfSaleCustomer();

        $taxID = $this->request->request->get('taxID');
        $name = $this->request->request->get('name');
        $result = [];

        if ($customer->saveNew($taxID, $name)) {
            Tools::log()->notice('Nuevo cliente registrado');
            $result = ['customer' => $customer->getCustomer()];
        }

        $this->buildResponse($result);
    }

    /**
     * Search customer by text.
     */
    protected function searchCustomer(): void
    {
        $customer = new PointOfSaleCustomer();
        $query = $this->request->request->get('query');

        $this->setResponse($customer->search($query));
    }

    /**
     * Search product by barcode.
     */
    protected function searchBarcode(): void
    {
        $barcode = $this->request->request->get('query');

        $this->setResponse(PointOfSaleProduct::searchBarcode($barcode));
    }

    /**
     * Search product by text.
     */
    protected function searchProduct(): void
    {
        $query = $this->request->request->get('query', '');
        $terminalCode = $this->request->request->get('terminal', '');
        $filters = $this->request->request->get('filters', '');

        $filterRules = json_decode($filters, true) ?: [];

        $terminal = PointOfSaleSession::getSessionTerminal($terminalCode);

        $company = $terminal->productsource === $terminal::PRODUCTS_FROM_COMPANY ? $terminal->idempresa : '';
        $warehouse = $terminal->productsource === $terminal::PRODUCTS_FROM_WAREHOUSE ? $terminal->codalmacen : '';

        $this->setResponse(PointOfSaleProduct::search($query, $filterRules, $warehouse, $company));
    }

    /**
     * Search product by text.
     */
    protected function searchStock(): void
    {
        $query = $this->request->request->get('query', '');

        $this->setResponse(PointOfSaleProduct::getStock($query));
    }

    /**
     * Put the order on hold.
     *
     * @return void
     */
    protected function saveDraft(): void
    {
        if (false === $this->validateRequest()) return;

        $request = new PointOfSaleRequest($this->request);
        $transaction = new PointOfSaleTransaction($request);

        $this->dataBase->beginTransaction();

        if (false === $transaction->saveDocument()) {
            Tools::log()->warning('pos-order-on-hold-error');
            $this->dataBase->rollback();
            return;
        }

        $this->dataBase->commit();
        Tools::log()->notice('pos-order-on-hold');

        $document = $transaction->getDocument();
        $this->setSuccessResponse([
            'code' => $document->id(),
            'model' => $document->modelClassName(),
            'order' => null,
        ]);
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

        if ($this->pipeFalse('saveBefore', $request, $transaction) === false) {
            return;
        }

        $this->dataBase->beginTransaction();

        if (false === $transaction->saveDocument()) {
            Tools::log('POS')->warning('fail-update');
            $this->dataBase->rollback();
            return;
        }

        $document = $transaction->getDocument();
        $payments = $transaction->getPayments();

        $order = new OrdenPuntoVenta();
        if (false === PointOfSaleStorage::saveOrder($order, $document)) {
            $this->dataBase->rollback();
            return;
        }

        if ((false === PointOfSaleStorage::completeDraftDocument($document))) {
            Tools::log('POS')->warning('fail-update-paused-document');

            $this->dataBase->rollback();
            return;
        }

        $this->session->savePayments($order, $payments);

        $this->dataBase->commit();

        $this->pipe('save', $document, $payments);
        Tools::log('POS')->notice('record-updated-correctly');

        $this->setSuccessResponse([
            'code' => $document->id(),
            'model' => $document->modelClassName(),
            'order' => $order->primaryColumnValue(),
        ]);
    }

    protected function printCashRegisterClosing(bool $reportZ = true): void
    {
        if ($reportZ) {
            $this->pipeFalse('printReportZ', $this->session->getSession(), $this->empresa, $this->request);

            return;
        }

        $this->pipeFalse('printReportX', $this->session->getSession(), $this->empresa, $this->request);
    }

    protected function printCashRegisterReportX(): void
    {
        $this->pipeFalse('printReportX', $this->session->getSession(), $this->empresa, $this->request);
    }

    protected function printCashRegisterReportZ(): void
    {
        $this->pipeFalse('printReportZ', $this->session->getSession(), $this->empresa, $this->request);
    }

    /**
     * Reprint order by code.
     */
    protected function printOrderTicket(): void
    {
        $documentCode = $this->request->request->get('document-code', '');
        $documentModel = $this->request->request->get('document-model', '');
        $documentOrder = $this->request->request->get('document-order', '');
        $request = $this->request->request;


        if ($documentModel === self::DRAFT_POS_DOCUMENT) {
            $document = PointOfSaleStorage::getDraftDocument($documentCode);
            $payments = [];
        } else if ($documentOrder) {
            $order = PointOfSaleStorage::getOrder($documentOrder);
            $document = $order->getDocument();
            $payments = $order->getPayments();
        } else {
            $order = PointOfSaleStorage::getOrderFromDocument($documentModel, $documentCode);
            $document = $order->getDocument();
            $payments = $order->getPayments();
        }

        Tools::log('POS')->info('printing-sale-ticket');

        $this->pipeFalse('printOrderTicket', $document, $payments, $request);
        $this->buildResponse();
    }

    /**
     * Reprint point of a sale document by code.
     */
    protected function printDraftTicket(): void
    {
        $code = $this->request->request->get('code', '');
        $request = $this->request->request;

        if (empty($code)) {
            Tools::log('POS')->warning('cant-print-ticket');
            return;
        }

        $document = PointOfSaleStorage::getDraftDocument($code);
        Tools::log('POS')->info('printing-draft-ticket');

        $this->pipeFalse('printOrderTicket', $document, [], $request);
        $this->buildResponse();
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

    /**
     * Close current user POS session.
     */
    protected function closeSession(): void
    {
        $cash = $this->request->request->getArray('cash') ?? [];

        if ($this->session->closeSession($cash)) {
            $this->printCashRegisterReportZ();

            $this->pipe('closeSession', $this->session->getSession());
        }

        $this->buildResponse();
    }

    protected function openSession(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        $terminal = $this->request->request->get('terminal', '');
        $amount = $this->request->request->get('saldoinicial', 0) ?: 0;
        $this->session->open($terminal, $amount);
    }

    /**
     * @return void
     */
    protected function openTerminal(): void
    {
        $id = $this->request->request->get('terminal', '');
        $this->session->getTerminal($id);
    }

    public function getDraftDocumentModel(): string
    {
        return self::DRAFT_POS_DOCUMENT;
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
