<?php

namespace FacturaScripts\Plugins\POS\Migration;

use FacturaScripts\Core\Template\MigrationClass;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\EstadoDocumento;

class CreateDefaultDraftStatuses extends MigrationClass
{
    const MIGRATION_NAME = 'create_default_draft_statuses_v2.76.0';

    public function run(): void
    {
        $this->createStatus('Abierto', 'fas fa-file-pen', true, true);
        $this->createStatus('Completado', 'fas fa-receipt', false, false);
    }

    private function createStatus(string $name, string $icon, bool $default, bool $editable): void
    {
        $where = [
            Where::eq('tipodoc', 'BorradorPuntoVenta'),
            Where::eq('nombre', $name),
        ];

        $status = new EstadoDocumento();
        if ($status->loadWhere($where)) {
            return;
        }

        $status->icon = $icon;
        $status->nombre = $name;
        $status->predeterminado = $default;
        $status->tipodoc = 'BorradorPuntoVenta';

        if (false === $editable) {
            $status->editable = false;
        }

        $status->save();
    }
}
