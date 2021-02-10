<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Model\Serie;
use FacturaScripts\Dinamic\Lib\POS\PrintProcessor;
use FacturaScripts\Dinamic\Lib\POS\SalesDataGrid;
use FacturaScripts\Dinamic\Lib\POS\SalesProcessor;
use FacturaScripts\Dinamic\Lib\POS\SalesSession;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\DenominacionMoneda;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\Variante;
use function json_encode;

/**
 * Controller to process Point of Sale Operations
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class POS extends Controller
{

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
        /** @noinspection PhpParamsInspection */
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
    private function execPreviusAction(string $action)
    {
        switch ($action) {
            case 'custom-search':
                $this->searchText();
                return false;

            case 'barcode-search':
                $this->searchBarcode();
                return false;

            case 'recalculate-document':
                $this->recalculateTransaction();
                return false;

            case 'resume-document':
                $this->resumeTransaction();
                return false;

            default:
                return true;
        }
    }

    private function searchText()
    {
        $query = $this->request->request->get('query');
        $target = $this->request->request->get('target');

        switch ($target) {
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

    private function searchBarcode()
    {
        $query = $this->request->request->get('query');
        $result = (new Variante())->codeModelSearch($query, 'referencia');

        $response = $result ? $result[0] : false;

        $this->response->setContent(json_encode($response));
    }

    private function recalculateTransaction()
    {
        $data = $this->request->request->all();
        $modelName = 'FacturaCliente';

        $salesProcessor = new SalesProcessor($modelName, $data);
        $result = $salesProcessor->recalculate();

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
                $this->saveTransaction();
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
     * Process sales.
     *
     * @return void
     */
    private function holdTransaction()
    {
        $data = $this->request->request->all();
        $modelName = 'OperacionPausada';

        if (false === $this->validateSaveRequest($data)) return;

        $salesProcessor = new SalesProcessor($modelName, $data);
        if ($salesProcessor->saveDocument(true)) {
            $this->toolBox()->i18nLog()->info('operation-is-paused');
        }
    }

    /**
     * Process sales.
     *
     * @return void
     */
    protected function saveTransaction()
    {
        $data = $this->request->request->all();
        $modelName = $data['tipo-documento'];
        $pausada = $data['idpausada'];

        $transactionModel = $this->request->request->get('tipo-documento', 'FacturaCliente');

        if (false === $this->validateSaveRequest($data)) return;

        $salesProcessor = new SalesProcessor($transactionModel, $data);
        if ($salesProcessor->saveDocument()) {
            $document = $salesProcessor->getDocument();
            $payments[] = $salesProcessor->getPayments();

            $this->session->storeOperation($document);
            $this->session->savePayments($payments);
            $this->session->updatePausedTransaction($pausada);
            $this->printTicket($document);
        }
    }

    /**
     * @param $data
     * @return bool
     */
    protected function validateSaveRequest($data)
    {
        if (false === $this->permissions->allowUpdate) {
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

    /**
     * @return void;
     */
    private function printCashup()
    {
        $ticketWidth = $this->session->terminal()->anchopapel;
        if (PrintProcessor::printCashup($this->session->getArqueo(), $this->empresa, $ticketWidth)) {
            $values = [
                '%ticket%' => 'Cierre caja',
                '%code%'=>'cashup'
            ];
            $this->toolBox()->i18nLog()->info('printing-ticket', $values);
            return;
        }

        $this->toolBox()->i18nLog()->warning('error-printing-ticket');
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
                '%code%'=>$document->modelClassName()
            ];
            $this->toolBox()->i18nLog()->info('printing-ticket', $values);
            return;
        }

        $this->toolBox()->i18nLog()->warning('error-printing-ticket');
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
    public function cashPaymentMethod(): string
    {
        return $this->toolBox()->appSettings()->get('pointofsale', 'fpagoefectivo');
    }

    /**
     * Returns all available payment methods.
     *
     * @return FormaPago[]
     */
    public function availablePaymentMethods(): array
    {
        $settings = $this->toolBox()->appSettings();
        $formasPago = [];

        $formasPagoCodeList = explode('|', $settings->get('pointofsale', 'formaspago'));
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
    public function getGridHeaders()
    {
        return SalesDataGrid::getDataGrid($this->user);
    }

    /**
     * Returns all available denominations.
     *
     * @return array
     */
    public function getDenominations()
    {
        return (new DenominacionMoneda())->all([], ['valor' => 'ASC']);
    }

    /**
     * Returns a random token to use as transaction id.
     *
     * @return string
     */
    public function requestToken()
    {
        return $this->multiRequestProtection->newToken();
    }

    public function customFieldList() : array
    {
        $path = FS_FOLDER . '/Dinamic/View/POS/Block/CustomField/';
        $list = scandir($path);

        if (false !== $list) {
            return array_diff($list, array('..', '.'));
        }

        return [];
    }

    /**
     * Load a paused document
     */
    private function resumeTransaction()
    {
        $code = $this->request->request->get('code', '');
        $result = $this->session->loadPausedTransaction($code);

        $this->response->setContent($result);
    }
}
