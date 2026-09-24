<?php

namespace FacturaScripts\Plugins\POS\Migration;

use FacturaScripts\Core\Template\MigrationClass;
use FacturaScripts\Plugins\POS\Model\LineaBorradorPuntoVenta;

class CreateDraftTables extends MigrationClass
{
    const MIGRATION_NAME = 'create_pos_draft_tables_v2.78.0';

    public function run(): void
    {
        new LineaBorradorPuntoVenta();
    }
}
