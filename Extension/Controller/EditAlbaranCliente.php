<?php

namespace FacturaScripts\Plugins\POS\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;

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
            }
        };
    }
}
