<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\POS\Lib\PointOfSaleForms;
use FacturaScripts\Plugins\POS\Model\OpcionesTerminalPuntoVenta;

/**
 * Controller to edit a single item from the Divisa model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class EditTerminalPuntoVenta extends ExtendedController\EditController
{
    public $selectedUser = '';

    /**
     * Returns the model name
     */
    public function getModelClassName()
    {
        return 'TerminalPuntoVenta';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'cash-register';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-cash-register';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }


    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('left');

        $this->createPaymenthMethodView();
        $this->createDocumentTypeView();
        $this->createTerminalFieldsView();
    }

    protected function createDocumentTypeView(string $viewName = 'EditTipoDocumentoPuntoVenta')
    {
        $this->addEditListView($viewName, 'TipoDocumentoPuntoVenta', 'doc-type', 'fas fa-file-invoice');
    }

    protected function createPaymenthMethodView(string $viewName = 'EditFormaPagoPuntoVenta')
    {
        $this->addEditListView($viewName, 'FormaPagoPuntoVenta', 'payment-methods', 'fas fa-credit-card');
        $this->views[$viewName]->disableColumn('codpago', false, 'false');
    }

    protected function createTerminalFieldsView(string $viewName = 'EditTerminalFields')
    {
        $this->addHtmlView($viewName, 'Master/EditTerminalFieldOption', 'TerminalPuntoVenta', 'pos-field-options', 'fas fa-users');
    }

    /**
     * @return bool
     */
    protected function insertAction()
    {
        if (parent::insertAction()) {
            return true;
        }

        if ($this->active === 'EditFormaPagoPuntoVenta') {
            $this->views['EditFormaPagoPuntoVenta']->disableColumn('codpago', false, 'false');
        }

        return false;
    }

    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditTipoDocumentoPuntoVenta':
            case 'EditFormaPagoPuntoVenta':
                $where = [new DataBaseWhere('idterminal', $this->getModel()->primaryColumnValue())];
                $view->loadData('', $where);
                break;

            case 'EditTerminalFields':

                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'load-fields-options':
                $this->selectedUser = $this->request->get('nick');
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

    private function deleteFieldOptions()
    {
        $this->selectedUser = $this->request->get('nick') ?: null;
        $options = new OpcionesTerminalPuntoVenta();

        $where = [
            new DataBaseWhere('nick', $this->selectedUser),
        ];

        if ($options->loadFromCode('', $where) && $options->delete()) {
            self::toolBox()::log()->notice('Configuracion de campos en el pos eliminado.');
        }
    }

    private function saveFieldOptions()
    {
        $fields = $this->request->get('field', []);
        $this->selectedUser = $this->request->get('nick') ?: null;
        $options = new OpcionesTerminalPuntoVenta();

        $where = [
            new DataBaseWhere('nick', $this->selectedUser)
        ];

        if (false === $options->loadFromCode('', $where)) {
            $options->nick = $this->selectedUser;
        }

        $options->columns = json_encode($fields);
        $options->save();
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
