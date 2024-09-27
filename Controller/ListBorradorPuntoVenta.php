<?php
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;

class ListBorradorPuntoVenta extends ListBusinessDocument
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "Pendientes";
        $data["menu"] = "point-of-sale";
        $data["icon"] = "fas fa-search";
        $data['showonmenu'] = false;

        return $data;
    }

    protected function createViews()
    {
        $this->createViewsOperacionPausada();
    }

    protected function createViewsOperacionPausada(string $viewName = "ListBorradorPuntoVenta")
    {
        $this->createViewSales($viewName, 'BorradorPuntoVenta', 'Pendientes');
    }
}
