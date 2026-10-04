<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Extension\Controller;

use Closure;

/**
 * @method createViewsDocuments(string $viewName, string $modelo, string $icono)
 */
class EditSecuenciaDocumento
{
    public function createViews(): Closure
    {
        return function () {
            $this->createViewsDocuments('ListBorradorPuntoVenta', 'BorradorPuntoVenta', 'cash-register');
        };
    }
}
