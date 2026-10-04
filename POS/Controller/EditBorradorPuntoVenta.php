<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Lib\AjaxForms\SalesController;

/**
 * Controller to edit a single item from the BorradorPuntoVenta model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class EditBorradorPuntoVenta extends SalesController
{

    /**
     * @return string
     */
    public function getModelClassName(): string
    {
        return 'BorradorPuntoVenta';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'point-of-sale';
        $data['title'] = 'delivery-note';
        $data['icon'] = 'fas fa-dolly-flatbed';
        $data['showonmenu'] = false;
        return $data;
    }
}
