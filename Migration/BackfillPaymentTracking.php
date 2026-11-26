<?php

declare(strict_types=1);

namespace FacturaScripts\Plugins\POS\Migration;

use FacturaScripts\Core\Template\MigrationClass;
use FacturaScripts\Plugins\POS\Model\PagoPuntoVentaSeguimiento;

/**
 * Creates initial tracking allocations for payments from existing POS operations.
 */
final class BackfillPaymentTracking extends MigrationClass
{
    public const MIGRATION_NAME = 'backfill_pos_payment_tracking_v2.89.0';

    public function run(): void
    {
        // Synchronize the table before executing the bulk insert.
        new PagoPuntoVentaSeguimiento();

        if (
            !$this->db()->tableExists('pos_operations')
            || !$this->db()->tableExists('pos_payments')
            || !$this->db()->tableExists('pos_payment_tracking')
        ) {
            return;
        }

        $this->exec(
            'INSERT INTO pos_payment_tracking'
            . ' (cantidad, codpago, idpagopos, idparent, idmodelfrom, idmodelto, modelfrom, modelto)'
            . ' SELECT (p.cantidad - p.cambio), p.codpago, p.idpago,'
            . ' NULL, o.iddocumento, o.iddocumento, o.tipodoc, o.tipodoc'
            . ' FROM pos_payments p'
            . ' INNER JOIN pos_operations o'
            . ' ON o.idoperacion = p.idoperacion'
            . ' WHERE NOT EXISTS ('
            . ' SELECT 1 FROM pos_payment_tracking t'
            . ' WHERE t.idpagopos = p.idpago'
            . ' AND t.idparent IS NULL'
            . ' AND t.idmodelto = o.iddocumento'
            . ' AND t.modelto = o.tipodoc'
            . ' )'
        );
    }
}
