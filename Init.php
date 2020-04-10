<?php
namespace FacturaScripts\Plugins\EasyPOS;

require_once __DIR__ . '/vendor/autoload.php';

use FacturaScripts\Core\Base\InitClass;


class Init extends InitClass
{

    public function init()
    {
        /// código a ejecutar cada vez que carga FacturaScripts (si este plugin está activado).
    }

    /**
     *
     */
    public function update()
    {
        /// código a ejecutar cada vez que se instala o actualiza el plugin
    }
}