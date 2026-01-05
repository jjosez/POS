<?php

namespace FacturaScripts\Plugins\POS;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\EstadoDocumento;
use FacturaScripts\Core\Template\InitClass;

class Init extends InitClass
{
    public function init(): void
    {
        $this->loadExtension(new Extension\Model\Familia());
        $this->loadExtension(new Extension\Model\Base\SalesDocument());
        $this->loadExtension(new Extension\Controller\EditEstadoDocumento());
        $this->loadExtension(new Extension\Controller\EditSecuenciaDocumento());
    }

    public function update(): void
    {
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

    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }
}
