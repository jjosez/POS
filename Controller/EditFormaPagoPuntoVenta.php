<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to edit a single item from the Divisa model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class EditFormaPagoPuntoVenta extends ExtendedController\EditController
{

    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'FormaPagoPuntoVenta';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'pos-payment-method';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-cash-register';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
