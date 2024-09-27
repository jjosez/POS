<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\AjaxForms\SalesController;

/**
 * Description of EditAlbaranCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditOperacionPausada extends SalesController
{

    /**
     * @return string
     */
    public function getModelClassName()
    {
        return 'OperacionPausada';
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
