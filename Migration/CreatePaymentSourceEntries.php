<?php

declare(strict_types=1);

namespace FacturaScripts\Plugins\POS\Migration;

use FacturaScripts\Core\Template\MigrationClass;
use FacturaScripts\Plugins\POS\Model\FuentePagoPuntoVenta;

final class CreatePaymentSourceEntries extends MigrationClass
{
    public const MIGRATION_NAME = 'create_pos_payment_source_entries_v1';

    public function run(): void
    {
        new FuentePagoPuntoVenta();
    }
}
