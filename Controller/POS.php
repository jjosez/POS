<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2026 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use Exception;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\POS\Lib\Core\BaseController;
use FacturaScripts\Plugins\POS\Lib\Core\SessionManager;
use FacturaScripts\Plugins\POS\Lib\Services\TransactionRequest;
use FacturaScripts\Plugins\POS\Lib\Services\Transactions;
use RuntimeException;

/**
 * POS controller.
 */
class POS extends BaseController
{
    const string DEFAULT_POS_DOCUMENT = 'FacturaCliente';
    const string DRAFT_POS_DOCUMENT = 'BorradorPuntoVenta';

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

        // Initialize minimal services
        $this->setupServices();

        // Initialize session and context (lazy loading)
        $this->session = new SessionManager($user);
        $this->setupContext();

        $action = $this->request->inputOrQuery('action', '');
        // Execute cart-specific actions
        if ($action && $this->execCartQueryAction($action)) {
            return;
        }

        // Execute actions
        if ($action && !$this->execAction($action)) {
            return;
        }

        $this->execAfterAction($action);

        // Load hooks
        $this->loadCustomDocumentFields();
        $this->loadCustomMenuElements();
        $this->loadPointOfSaleHooks();

        $template = $this->session->getView();
        $this->setTemplate($template);
    }

    /**
     * Execute main POS actions.
     */
    protected function execAction(string $action): bool
    {
        switch ($action) {
            case 'product:barcode:search':
                $this->searchBarcode();
                return false;

            case 'product:stock:get':
                $query = $this->request->request->get('query', '');
                $this->setResponse($this->context->products()->getStock($query));
                return false;

            case 'product:images:get':
                $id = $this->request->request->get('id', '');
                $code = $this->request->request->get('code', '');
                $this->setResponse($this->context->products()->getImagesUrl($id, $code));
                return false;

            case 'order:save':
                $this->saveOrder();
                $this->buildResponse();
                return false;

            case 'order:refund:get':
                $this->getOrderToRefund();
                return false;

            case 'order:last:list':
                $this->setResponse($this->context->storage()->getOrders());
                return false;

            case 'order:draft:save':
                $this->saveDraft();
                return false;

            case 'order:draft:resume':
                $this->resumeOrder();
                return false;

            case 'order:draft:list':
                $this->setResponse($this->context->storage()->getDrafts());
                return false;

            case 'family:filter:set':
                $codfamilia = $this->request->request->get('query', '');
                $result = $this->context->families()->getFamilyHierarchy($codfamilia);
                $this->setResponse($result);
                return false;

            case 'print:draft':
                $this->printDraftTicket();
                return false;

            case 'print:ticket':
                $this->printOrderTicket();
                return false;

            case 'print:report:x':
                $this->printCashRegisterReportX();
                $this->buildResponse();
                return false;

            case 'session:close':
                $this->closeSession();
                return false;

            case 'session:cash:entry':
                $this->saveCashEntry();
                return true;

            case 'session:cash:withdraw':
                $this->saveCashWithdraw();
                return true;

            default:
                return true;
        }
    }

    /**
     * Execute cart-specific actions (no session required).
     */
    protected function execCartQueryAction(string $action): bool
    {
        switch ($action) {
            case 'order:draft:delete':
                $this->deleteDraftOrder();
                return true;

            case 'order:recalculate':
                $this->recalculateOrder();
                return true;

            case 'customer:create':
                $this->saveNewCustomer();
                return true;

            case 'customer:search':
                $query = $this->request->request->get('query');
                $this->setResponse($this->context->customers()->search($query));
                return true;

            case 'product:search':
                $this->searchProduct();
                return true;

            default:
                return false;
        }
    }

    protected function execAfterAction(string $action): void
    {
        switch ($action) {
            case 'user:change':
                $this->changeUser();
                break;
            case 'session:open':
                $this->openSession();
                break;
            case 'terminal:open':
                $this->openTerminal();
                break;
        }
    }

    // ========================================================================
    // Draft Operations
    // ========================================================================

    protected function deleteDraftOrder(): void
    {
        if (!$this->validateDelete()) {
            $this->buildResponse();
            return;
        }

        $code = $this->request->request->get('code', '');
        $this->context->storage()->deleteDraft($code);

        $this->setNewToken();
        $this->buildResponse();
    }

    protected function resumeOrder(): void
    {
        $code = $this->request->request->get('code', '');
        if (!$code) {
            return;
        }

        $draft = $this->context->storage()->getDraft($code);
        if (!$draft) {
            return;
        }

        $result = ['doc' => $draft, 'lines' => $draft->getLines()];
        $this->setNewToken();
        $this->buildResponse($result);
    }

    protected function saveDraft(): void
    {
        if (!$this->validateRequest()) {
            return;
        }

        $request = new TransactionRequest($this->request);
        $transaction = new Transactions($request);

        $this->dataBase->beginTransaction();

        if (!$transaction->saveDocument()) {
            Tools::log()->warning('pos-order-on-hold-error');
            $this->dataBase->rollback();
            return;
        }

        $this->dataBase->commit();
        $this->addMessage('pos-order-on-hold');

        $document = $transaction->getDocument();
        $this->setSuccessResponse([
            'code' => $document->id(),
            'model' => $document->modelClassName(),
            'order' => null
        ]);

        $this->buildResponse();
    }

    // ========================================================================
    // Order Operations
    // ========================================================================

    protected function getOrderToRefund(): void
    {
        $code = $this->request->input('code', '');

        try {
            $data = $this->context->storage()->getOrderForRefund($code);

            $this->buildResponse([
                'success' => true,
                'doc' => $data['document'],
                'lines' => $data['lines'],
            ]);
        } catch (Exception $e) {
            $this->buildResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function recalculateOrder(): void
    {
        $request = new TransactionRequest($this->request);
        $transaction = new Transactions($request);

        $this->setResponse($transaction->recalculate());
    }

    protected function saveOrder(): void
    {
        if (!$this->validateRequest()) {
            return;
        }

        $request = new TransactionRequest($this->request);
        $transaction = new Transactions($request);

        if ($this->pipeFalse('saveBefore', $request, $transaction) === false) {
            $this->buildResponse();
            return;
        }

        $this->dataBase->beginTransaction();

        if (!$transaction->saveDocument()) {
            $this->dataBase->rollback();
            $this->buildResponse();
            return;
        }

        $document = $transaction->getDocument();
        $payments = $transaction->getPayments();

        $order = new OrdenPuntoVenta();
        if (!$this->context->storage()->saveOrder($order, $document)) {
            Tools::log('POS')->warning('fail-save-order');
            $this->dataBase->rollback();
            $this->buildResponse();
            return;
        }

        if (!$this->context->storage()->completeDraft($document)) {
            Tools::log('POS')->warning('fail-update-paused-document');
            $this->dataBase->rollback();
            $this->buildResponse();
            return;
        }

        // Save payments and receipts
        if (!$this->context->payments()->savePayments($document, $order, $payments)) {
            Tools::log('POS')->warning('fail-save-payments');
            $this->dataBase->rollback();
            $this->buildResponse();
            return;
        }

        $this->dataBase->commit();

        $this->pipe('save', $document, $payments);
        Tools::log('POS')->notice('record-updated-correctly');

        $this->setSuccessResponse([
            'code' => $document->id(),
            'model' => $document->modelClassName(),
            'order' => $order->id()
        ]);
    }

    protected function executeTransaction(Transactions $transaction): bool
    {
        try {
            $this->dataBase->beginTransaction();

            if (!$transaction->saveDocument()) {
                throw new RuntimeException('fail-update');
            }

            $document = $transaction->getDocument();
            $payments = $transaction->getPayments();
            $order = new OrdenPuntoVenta();

            if (!$this->context->storage()->saveOrder($order, $document)) {
                throw new RuntimeException('fail-save-order');
            }

            if (!$this->context->storage()->completeDraft($document)) {
                throw new RuntimeException('fail-update-paused-document');
            }

            if (!$this->context->payments()->savePayments($document, $order, $payments)) {
                throw new RuntimeException('fail-save-payments');
            }

            $this->dataBase->commit();

            $this->pipe('save', $document, $payments);
            Tools::log('POS')->notice('record-updated-correctly');

            $this->setSuccessResponse([
                'code' => $document->id(),
                'model' => $document->modelClassName(),
                'order' => $order->id(),
                'token' => $order->id(),
            ]);

            return true;
        } catch (Exception $exception) {
            $this->dataBase->rollback();
            Tools::log('POS')->warning($exception->getMessage());
            return false;
        }
    }

    // ========================================================================
    // Cash Operations
    // ========================================================================

    protected function saveCashEntry(): void
    {
        if (!$this->validateRequest()) {
            return;
        }

        $amount = $this->request->request->get('amount', 0);
        $description = $this->request->request->get('description');

        if (!is_numeric($amount) || $amount <= 0) {
            $this->addMessage('invalid-amount', 'warning');
            return;
        }

        if ($this->context->storage()->recordCashMovement($amount, $description)) {
            $this->addMessage('cash-entry-ok');
        }

        $this->buildResponse();
    }

    protected function saveCashWithdraw(): void
    {
        if (!$this->validateRequest()) {
            return;
        }

        $amount = $this->request->input('amount', 0);
        $description = $this->request->input('description');

        if (!is_numeric($amount) || $amount <= 0) {
            $this->addMessage('invalid-amount', 'warning');
            return;
        }

        $amount *= -1;
        if ($this->context->storage()->recordCashMovement($amount, $description)) {
            $this->addMessage('cash-withdraw-ok');
        }

        $this->buildResponse();
    }

    // ========================================================================
    // Customer Operations
    // ========================================================================

    protected function saveNewCustomer(): void
    {
        $taxID = $this->request->request->get('taxID');
        $name = $this->request->request->get('name');
        $result = [];

        if ($this->context->customers()->saveNew($taxID, $name)) {
            $this->addMessage('save-ok');
            $result = ['customer' => $this->context->customers()->getCustomer()];
        }

        $this->buildResponse($result);
    }

    // ========================================================================
    // Product Operations
    // ========================================================================

    protected function searchProduct(): void
    {
        $query = $this->request()->request->get('query', '');
        $filters = $this->request()->request->get('filters', '');

        $filterRules = json_decode($filters, true) ?: [];
        $terminal = $this->context->config()->getTerminal();

        $company = $terminal->productsource === $terminal::PRODUCTS_FROM_COMPANY ? $terminal->idempresa : '';
        $warehouse = $terminal->productsource === $terminal::PRODUCTS_FROM_WAREHOUSE ? $terminal->codalmacen : '';

        $this->setResponse($this->context->products()->search($query, $filterRules, $warehouse, $company));
    }

    protected function searchBarcode(): void
    {
        $barcode = $this->request->request->get('query');
        $result = $this->context->products()->searchBarcode($barcode);

        if (!$result) {
            $this->addMessage('barcode-not-found', 'info');
        }

        $this->buildResponse($result);
    }

    // ========================================================================
    // Print Operations
    // ========================================================================

    protected function printOrderTicket(): void
    {
        $documentCode = $this->request->request->get('document-code', '');
        $documentModel = $this->request->request->get('document-model', '');
        $documentOrder = $this->request->request->get('document-order', '');

        if ($documentModel === self::DRAFT_POS_DOCUMENT) {
            $document = $this->context->storage()->getDraft($documentCode);
            $payments = [];
        } elseif ($documentOrder) {
            $order = $this->context->storage()->getOrder($documentOrder);
            $document = $order->getDocument();
            $payments = $order->getPayments();
        } else {
            $order = $this->context->storage()->getOrderFromDocument($documentModel, $documentCode);
            $document = $order->getDocument();
            $payments = $order->getPayments();
        }

        $this->addMessage('printing-sale-ticket');
        $this->pipeFalse('printOrderTicket', $document, $payments, $this->request);
        $this->buildResponse();
    }

    protected function printDraftTicket(): void
    {
        $code = $this->request->request->get('code', '');
        if (empty($code)) {
            $this->addMessage('cant-print-ticket', 'warning');
            return;
        }

        $document = $this->context->storage()->getDraft($code);
        $this->addMessage('printing-draft-ticket');

        $this->pipeFalse('printOrderTicket', $document, [], $this->request);
        $this->buildResponse();
    }

    protected function printCashRegisterReportX(): void
    {
        $this->pipeFalse('printReportX', $this->session->getSession(), $this->empresa, $this->request);
    }

    protected function printCashRegisterReportZ(): void
    {
        $this->pipeFalse('printReportZ', $this->session->getSession(), $this->empresa, $this->request);
    }

    // ========================================================================
    // Session Operations
    // ========================================================================

    protected function changeUser(): void
    {
        // TODO: Implement if needed
    }

    protected function closeSession(): void
    {
        $cash = $this->request->request->getArray('cash') ?? [];

        if ($this->session->close($cash)) {
            $this->printCashRegisterReportZ();
            $this->pipe('closeSession', $this->session->getSession());
        }

        $this->buildResponse();
    }

    protected function openSession(): void
    {
        if (!$this->validateFormToken()) {
            return;
        }

        $terminal = $this->request->request->get('terminal', '');
        $amount = $this->request->request->get('saldoinicial', 0) ?: 0;
        $this->session->open($terminal, $amount);
    }

    protected function openTerminal(): void
    {
        $id = $this->request->request->get('terminal', '');
        $this->session->getTerminal($id);

        $this->setupContext();
    }

    // ========================================================================
    // Page Data
    // ========================================================================

    public function getDraftDocumentModel(): string
    {
        return self::DRAFT_POS_DOCUMENT;
    }

    /**
     * Returns some products to populate starting product list.
     */
    public function getHomeProducts(): array
    {
        $filters = ['codcliente' => $this->context->terminal()->codcliente];
        return $this->context->products()->search('', $filters);
    }

    /**
     * Returns all app settings as a single array for JavaScript.
     */
    public function getAppSettings(): array
    {
        $config = $this->context->config();
        $currency = $this->context->currency();
        $defaultCustomer = $config->getDefaultCustomer();
        $defaultDocument = $config->getDefaultDocument();
        $terminal = $this->session->getTerminal();

        return [
            'cash' => $config->getCashPaymentMethod(),
            'token' => $this->multiRequestProtection->newToken(),
            'url' => 'POS',
            'codalmacen' => $config->getDefaultWarehouse(),
            'customer' => [
                'codcliente' => $defaultCustomer->codcliente,
                'nombre' => $defaultCustomer->nombre
            ],
            'document' => [
                'code' => $defaultDocument->tipodoc,
                'serie' => $defaultDocument->codserie,
                'description' => $defaultDocument->primaryDescription(),
                'draft-document' => self::DRAFT_POS_DOCUMENT
            ],
            'currency' => [
                'divisa' => $currency->getCurrency()->coddivisa,
                'decimals' => $currency->getDecimals(),
                'separator' => $currency->getSeparator(),
                'symbol' => $currency->getCurrency()->simbolo,
            ],
            'payment' => [
                'codpago' => $config->getCashPaymentMethod()
            ],
            'terminal' => $terminal->idterminal,
            'cart' => [
                'freeLines' => $terminal->free_cart_lines,
                'groupLines' => $terminal->group_cart_lines,
            ],
            'productsearch' => [
                'templateDisplayMode' => $terminal->getProductDisplayMode()
            ],
            'supported-documents' => $terminal->getSupportedDocuments(),
            'agents' => $this->context->agents()->getAgentsList()
        ];
    }

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
