<?php
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Model\EstadoDocumento;

class EditConfiguracionPOS extends ExtendedController\PanelController
{
    public $paymentMethods;
    public $businessDocTypes;

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
        $this->addHtmlView('SalesPointGeneralSettings', 'SalesPointGeneralSettings', 'FormaPago', 'general', 'fas fa-cogs');
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

    public function isBusinessDocEneabled($doc)
    {
        $tiposdocumento = explode('|', AppSettings::get('pointofsale', 'tiposdocumento'));
        if ($tiposdocumento) {
            return in_array($doc, $tiposdocumento);
        }

        return false;
    }

    private function loadSalesPointGeneralSettings()
    {
        $this->paymentMethods = $this->getEneabledPaymentMethod();
        $this->businessDocTypes = $this->getBusinessDocumentTypes();

        $action = $this->request->get('action');
        if ($action) {
            $appSettings = new AppSettings();

            $formaspago = $this->request->get('paymentmethod');
            if ($formaspago) {                
                $appSettings->set('pointofsale', 'formaspago',join('|', $formaspago));
            }

            $fpagoefectivo = $this->request->request->get('cash-payment');
            if ($fpagoefectivo) {               
                $appSettings->set('pointofsale', 'fpagoefectivo', $fpagoefectivo);                               
            }

            $tiposdocumento = $this->request->request->get('bussinesdocs');
            if ($fpagoefectivo) {               
                $appSettings->set('pointofsale', 'tiposdocumento', join('|', $tiposdocumento));
            }

            $appSettings->save();  
        }
    }

    private function getBusinessDocumentTypes()
    {
        //SELECT DISTINCT tipodoc FROM estados_documentos WHERE tipodoc LIKE '%Cliente'
        $where = [
            new DataBaseWhere('predeterminado', true),
            new DataBaseWhere('tipodoc', '%Cliente', 'LIKE'),
        ];

        return (new EstadoDocumento)->all($where);
    }
}