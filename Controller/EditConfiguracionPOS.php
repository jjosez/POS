<?php
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Model\FormaPago;

class EditConfiguracionPOS extends ExtendedController\PanelController
{
    public $paymentMethods;

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'options';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-cogs';

        return $pagedata;
    }

    protected function createViews()
    {
        $this->setTemplate('SalesPointSettings');

        $this->addListView('ListFraccionMoneda', 'FraccionMoneda', 'currency-fraction');
        $this->addHtmlView('SalesPointGeneralSettings', 'SalesPointGeneralSettings', 'FormaPago', 'general');
    }

    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListFraccionMoneda':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;
            case 'SalesPointGeneralSettings':
                $this->loadSalesPointGeneralSettings();
                break;
        }
    }

    private function getEneabledPaymentMethod()
    {
        //$where = [new DataBaseWhere('codfabricante', $codfabricante)];
        return (new FormaPago)->all();
    }

    public function isPaymentMethodEneabled($codpago)
    {
        $formaspago = explode('|', AppSettings::get('pointofsale', 'formaspago'));
        if ($formaspago) {
            return in_array($codpago, $formaspago);
        }

        return false;
    }

    private function loadSalesPointGeneralSettings()
    {
        $this->paymentMethods = $this->getEneabledPaymentMethod();

        $action = $this->request->get('action');
        if ($action) {
            $formaspago = $this->request->get('paymentmethod');

            if ($formaspago) {
                $appSettings = new AppSettings();
                $appSettings->set('pointofsale', 'formaspago',join('|', $formaspago));
                $appSettings->save();
            }
        }
    }
}