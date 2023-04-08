<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to edit a single item from the DenominacionMoneda model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class EditDenominacionMoneda extends ExtendedController\EditController
{

    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'DenominacionMoneda';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'currency-denomination';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-money-bill-alt';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
