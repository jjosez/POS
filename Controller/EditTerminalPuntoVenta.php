<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleForms;
use FacturaScripts\Plugins\POS\Model\DenominacionMoneda;
use FacturaScripts\Plugins\POS\Model\OpcionesTerminalPuntoVenta;

/**
 * Controller to edit a single item from the Divisa model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class EditTerminalPuntoVenta extends ExtendedController\EditController
{
    public ?string $selectedUser = '';
    const EDIT_DOCUMENT_TYPE_VIEW = 'EditTipoDocumentoPuntoVenta';
    const EDIT_PAYMENT_METHOD_VIEW = 'EditFormaPagoPuntoVenta';
    const EDIT_TERMINAL_FIELDS_VIEW = 'EditTerminalFields';

    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'TerminalPuntoVenta';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'cash-register';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-cash-register';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }


    protected function createViews(): void
    {
        parent::createViews();
        $this->setTabsPosition('left');

        $this->createPaymenthMethodView();
        $this->createDocumentTypeView();
        $this->createDenominationsView();
        $this->createTerminalFieldsView();
        $this->createTerminalSessionView();
    }

    protected function createDocumentTypeView(string $viewName = self::EDIT_DOCUMENT_TYPE_VIEW): void
    {
        $modelName = 'TipoDocumentoPuntoVenta';
        $this->addEditListView($viewName, $modelName, 'doc-type', 'fas fa-file-invoice');
    }

    protected function createPaymenthMethodView(string $viewName = self::EDIT_PAYMENT_METHOD_VIEW): void
    {
        $modelName = 'FormaPagoPuntoVenta';
        $this->addEditListView($viewName, $modelName, 'payment-methods', 'fas fa-credit-card');
        $this->views[$viewName]->disableColumn('codpago', false, 'false');
    }

    protected function createTerminalFieldsView(string $viewName = self::EDIT_TERMINAL_FIELDS_VIEW): void
    {
        $modelName = 'TerminalPuntoVenta';
        $this->addHtmlView($viewName, 'Master/EditTerminalFieldOption', $modelName, 'pos-field-options', 'fas fa-users');
    }

    protected function createTerminalSessionView($viewName = 'ListSesionPuntoVenta'): void
    {
        $this->addListView($viewName, 'SesionPuntoVenta', 'Sesiones', 'fas fa-user');

        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    protected function createDenominationsView($viewName = 'ListDenominacionMoneda'): void
    {
        $this->addListView($viewName, 'DenominacionMoneda', 'currency-denomination', 'fas fa-dollar-sign')
            ->setSettings('modalInsert', 'add-denomination');
    }

    protected function execPreviousAction($action): bool
    {
        if ($action == 'add-denomination') {
            return $this->saveDenominationAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * @return bool
     */
    protected function insertAction(): bool
    {
        if (parent::insertAction()) {
            return true;
        }

        if ($this->active === self::EDIT_PAYMENT_METHOD_VIEW) {
            $view = $this->views[self::EDIT_PAYMENT_METHOD_VIEW];
            $view->disableColumn('codpago', false, 'false');
        }

        return false;
    }

    protected function loadData($viewName, $view): void
    {
        $where = [new DataBaseWhere('idterminal', $this->getModel()->primaryColumnValue())];

        switch ($viewName) {
            case self::EDIT_DOCUMENT_TYPE_VIEW:
            case self::EDIT_PAYMENT_METHOD_VIEW:
                $view->loadData('', $where);
                break;
            case 'ListSesionPuntoVenta':
                $orderBy = ['fechainicio' => 'DESC'];
                $view->loadData('', $where, $orderBy);
                break;
            case 'EditTerminalFields':
                break;
            case 'ListDenominacionMoneda':
                $view->loadData('', []);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function execAfterAction($action): void
    {
        switch ($action) {
            case 'load-fields-options':
                $this->selectedUser = $this->request->inputOrQuery('nick');
                break;
            case 'save-fields-options':
                $this->saveFieldOptions();
                break;

            case 'delete-fields-options':
                $this->deleteFieldOptions();
                break;

            default:
                parent::execAfterAction($action);
                break;
        }
    }

    private function deleteFieldOptions(): void
    {
        $this->selectedUser = $this->request->inputOrQuery('nick') ?: null;
        $options = new OpcionesTerminalPuntoVenta();

        if ($options->loadWhereEq('nick', $this->selectedUser) && $options->delete()) {
            Tools::log()->notice('Configuracion de campos en el pos eliminado.');
        }
    }

    private function saveFieldOptions(): void
    {
        $fields = $this->request->inputOrQuery('field', []);
        $this->selectedUser = $this->request->inputOrQuery('nick') ?: null;
        $options = new OpcionesTerminalPuntoVenta();

        if (false === $options->loadWhereEq('nick', $this->selectedUser)) {
            $options->nick = $this->selectedUser;
        }

        $options->columns = json_encode($fields);
        $options->save();
    }

    private function saveDenominationAction(): bool
    {
        $code = $this->request->inputOrQuery('clave');
        $currency = $this->request->inputOrQuery('coddivisa');
        $value = $this->request->inputOrQuery('valor');

        $denomination = new DenominacionMoneda();

        $denomination->clave = $code;
        $denomination->coddivisa = $currency;
        $denomination->valor = $value;

        if ($denomination->save()) {
            Tools::log()->notice('save-ok.');
            return true;
        }

        return false;
    }

    public function getTerminalFields(): array
    {
        return PointOfSaleForms::getFormsGrid($this->selectedUser ?? '');
    }

    public function getUserList(): array
    {
        $result = [];
        $users = CodeModel::all(User::tableName(), 'nick', 'nick', false);

        foreach ($users as $codeModel) {
            $result[$codeModel->code] = $codeModel->description;
        }

        return $result;
    }
}
