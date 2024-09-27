<?php

namespace FacturaScripts\Plugins\POS;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\EstadoDocumento;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\POS\Model\TerminalPuntoVenta;

class Init extends InitClass
{
    public function init(): void
    {
        //$this->loadExtension(new Extension\Controller\EditFamilia());
        $this->loadExtension(new Extension\Controller\EditAlbaranCliente());
        //$this->loadExtension(new Extension\Controller\EditPedidoCliente());
        //$this->loadExtension(new Extension\Lib\BusinessDocumentGenerator());
        $this->loadExtension(new Extension\Model\FacturaCliente());
        $this->loadExtension(new Extension\Model\Base\SalesDocument());
        $this->loadExtension(new Extension\Model\Base\SalesDocument());
        $this->loadExtension(new Extension\Controller\EditEstadoDocumento());
    }

    public function update(): void
    {
        $this->updateTerminaPuntoVentaTable();
        $this->updateTerminals();

        $this->createDraftDocumentOpenStatus();
        $this->createDraftDocumentCompletedStatus();
    }

    protected function createDraftDocumentOpenStatus()
    {
        $where = [
            new DataBaseWhere('tipodoc', 'BorradorPuntoVenta'),
            new DataBaseWhere('nombre', 'Abierto'),
        ];

        $status = new EstadoDocumento();

        if (false === $status->loadFromCode('', $where)) {
            $status->icon = 'fas fa-file-pen';
            $status->nombre = 'Abierto';
            $status->predeterminado = true;
            $status->tipodoc = 'BorradorPuntoVenta';

            $status->save();
        }
    }

    protected function createDraftDocumentCompletedStatus()
    {
        $where = [
            new DataBaseWhere('tipodoc', 'BorradorPuntoVenta'),
            new DataBaseWhere('nombre', 'Completado'),
        ];

        $status = new EstadoDocumento();

        if (false === $status->loadFromCode('', $where)) {
            $status->icon = 'fas fa-receipt';
            $status->editable = false;
            $status->nombre = 'Completado';
            $status->predeterminado = false;
            $status->tipodoc = 'BorradorPuntoVenta';

            $status->save();
        }
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

                Tools::log()->warning('Updated terminalespos table.');
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

    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }
}
