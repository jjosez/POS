<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Model\EstadoDocumento;

/**
 * Controller to edit POS settings
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
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
        $this->setTemplate('EditConfiguracionPOS');

        $this->addHtmlView('GeneralSettingsPOS', 'GeneralSettingsPOS', 'FormaPago', 'general', 'fas fa-cogs');
        $this->addListView('ListDenominacionMoneda', 'DenominacionMoneda', 'currency-denomination');     
    }

    protected function loadData($viewName, $view)
    {
        $this->hasData = true;
        switch ($viewName) {
            case 'ListDenominacionMoneda':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;
            case 'GeneralSettingsPOS':
                $this->loadGeneralSettingsPOS();
                break;
        }
    }

    private function getCustumerBusinessDocuments()
    {
        /*SELECT DISTINCT tipodoc FROM estados_documentos WHERE tipodoc LIKE '%Cliente'*/
        $where = [
            new DataBaseWhere('predeterminado', true),
            new DataBaseWhere('tipodoc', '%Cliente', 'LIKE'),
        ];

        return (new EstadoDocumento)->all($where);
    }

    private function getEneabledPaymentMethod()
    {
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

    private function loadGeneralSettingsPOS()
    {
        $this->paymentMethods = $this->getEneabledPaymentMethod();
        $this->businessDocTypes = $this->getCustumerBusinessDocuments();

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

            $defauldocumento = $this->request->request->get('default-businessdoc');
            if ($defauldocumento) {               
                $appSettings->set('pointofsale', 'defaultdoc', $defauldocumento);
            }

            $appSettings->save();  
        }
    }
}
