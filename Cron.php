<?php

namespace FacturaScripts\Plugins\POS;

use FacturaScripts\Core\Template\CronClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\POS\Model\BorradorPuntoVenta;

class Cron extends CronClass
{

    public function run(): void
    {
        $this->job('delete-point-of-sale-drafts')
            ->everySundayAt('23')
            ->run(function () {

                foreach (BorradorPuntoVenta::allCompleted() as $operacion) {
                    $operacion->editable = true;

                    if (!$operacion->delete()) {
                        Tools::log('POS')->warning('Error al eliminar operacion pausada');
                    }
                }
            });
    }
}
