<?php

namespace FacturaScripts\Plugins\POS\Extension\Controller;

use Closure;

class EditFamilia
{
    public function createViews(): Closure
    {
        return function () {
            $this->createViewPOS();
        };
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewPOS(): Closure
    {
        return function (string $viewName = 'FamiliaImagen') {
            $this->addHtmlView($viewName, 'Tab\FamiliaImagen', 'familia', 'pos-settings', 'fas fa-shopping-cart');
        };
    }
}
