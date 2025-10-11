<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;

/**
 * Controller to edit a single item from the SesionPOS model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class EditPagoPuntoVenta extends ExtendedController\EditController
{
    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'PagoPuntoVenta';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'pos-payments';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-donate';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    protected function setViewReadOnly(string $viewName, ExtendedController\BaseView $view): void
    {
        /** @var SesionPuntoVenta $session */
        $session = $view->model->getSesionPuntoVenta();

        /** @var OrdenPuntoVenta $order */
        $order = $view->model->getOrdenPuntoVenta();

        if ($session->abierto && $order->getDocument()->editable) return;

        $this->views[$viewName]->setReadOnly(true);
    }

    protected function loadData($viewName, $view): void
    {
        if ($viewName === $this->getMainViewName())
        {
            parent::loadData($viewName, $view);

            $this->setSettings($viewName, 'btnNew', false);
            $this->setViewReadOnly($viewName, $view);

            return;
        }

        parent::loadData($viewName, $view);
    }
}
