<?php
namespace FacturaScripts\Plugins\POS;

use FacturaScripts\Core\Base\InitClass;

class Init extends InitClass
{

    public function init()
    {
    }

    /**
     *
     */
    public function update()
    {
        new Model\SesionPOS();
    }
}