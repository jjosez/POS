<?php
namespace FacturaScripts\Plugins\POS;

use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Plugins\POS\Model\TerminalPuntoVenta;

class Init extends InitClass
{
    public function init()
    {
    }

    public function update()
    {
        new Model\SesionPuntoVenta();
        $this->updateTerminals();
    }

    private function updateTerminals(): void
    {
        foreach ((new TerminalPuntoVenta())->all() as $terminal) {
            if ($terminal->idempresa) {
                continue;
            }
            $terminal->save();
        }
    }
}
