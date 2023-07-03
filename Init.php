<?php

namespace FacturaScripts\Plugins\POS;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Plugins\POS\Model\TerminalPuntoVenta;

class Init extends InitClass
{
    public function init()
    {
        //$this->loadExtension(new Extension\Controller\EditFamilia());
        $this->loadExtension(new Extension\Model\FacturaCliente());
        $this->loadExtension(new Extension\Model\Base\SalesDocument());
        $this->loadExtension(new Extension\Controller\EditAlbaranCliente());
    }

    public function update()
    {
        $this->updateTerminaPuntoVentaTable();
        $this->updateTerminals();
    }

    private function updateTerminaPuntoVentaTable()
    {
        $database = new DataBase();
        if (false === $database->tableExists('terminalespos')) {
            return;
        }

        foreach ($database->getColumns('terminalespos') as $column) {
            if ($column['name'] === 'codserie') {
                $database->exec('ALTER TABLE terminalespos DROP FOREIGN KEY ca_terminalespos_series;');
                $database->exec('ALTER TABLE terminalespos DROP COLUMN codserie;');

                $this->toolBox()::log()->warning('Updated terminalespos table.');
            }
        }
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
