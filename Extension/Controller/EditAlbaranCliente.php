<?php

namespace FacturaScripts\Plugins\POS\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;

/**
 * @method addEditListView(string $viewName, string $string, string $string1, string $string2)
 * @method getModel()
 */
class EditAlbaranCliente
{
    public function createViews(): Closure
    {
        return function () {
            $this->createViewPagosPOS();
        };
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewPagosPOS(): Closure
    {
        return function (string $viewName = 'EditPagoPuntoVenta') {
            $this->addEditListView($viewName, 'PagoPuntoVenta', 'Pagos POS', 'fas fa-donate');
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {

            if ($viewName === 'EditPagoPuntoVenta') {
                $posOrder = new OrdenPuntoVenta();
                $iddocumento = $this->getModel()->primaryColumnValue();

                if (false === $posOrder->loadFromDocument('AlbaranCliente', $iddocumento)) {
                    return;
                }

                $where = [
                    new DataBaseWhere('idoperacion', $posOrder->primaryColumnValue()),
                ];

                $view->loadData('', $where);

                if (false === $this->getModel()->editable || false === $this->permissions->allowUpdate) {
                    $this->views[$viewName]->disableColumn('amount', false, 'true');
                    $this->views[$viewName]->disableColumn('change-amount', false, 'true');
                    $this->views[$viewName]->disableColumn('payment-method', false, 'true');

                    $this->setSettings($viewName, 'btnNew', false);
                    $this->setSettings($viewName, 'btnDelete', false);
                    $this->setSettings($viewName, 'btnSave', false);
                    $this->setSettings($viewName, 'btnUndo', false);
                }
            }
        };
    }
}
