<?php

namespace FacturaScripts\Plugins\POS\Migration;

use FacturaScripts\Core\Base\DataBase\PostgresqlEngine;
use FacturaScripts\Core\Template\MigrationClass;

class RenameTables extends MigrationClass
{
    const MIGRATION_NAME = 'rename_pos_tables_v2.77.0';

    public function run(): void
    {
        $renames = [
            'pausadaspos' => 'pos_drafts',
            'lineaspausadaspos' => 'pos_draft_lines',
            'terminalespos' => 'pos_terminals',
            'sesionespos' => 'pos_sessions',
            'operacionespos' => 'pos_operations',
            'pagospos' => 'pos_payments',
            'formaspagopos' => 'pos_payment_methods',
            'movimientospos' => 'pos_cash_movements',
            'denominacionesmoneda' => 'pos_currency_denominations',
            'tiposdocpos' => 'pos_document_types',
            'terminalespos_options' => 'pos_terminal_options',
            'pagospos_tracking' => 'pos_payment_tracking',
            'pos_refunds_drafts' => 'pos_refund_drafts',
        ];

        $db = $this->db();
        $existing = $db->getTables();
        $isPostgres = $db->getEngine() instanceof PostgresqlEngine;

        $pending = [];
        foreach ($renames as $old => $new) {
            if (in_array($old, $existing, true) && !in_array($new, $existing, true)) {
                $pending[$old] = $new;
            }
        }

        if (empty($pending)) {
            return;
        }

        if ($isPostgres) {
            $this->renamePostgres($db, $pending);
        } else {
            $this->renameMysql($db, $pending);
        }
    }

    private function renamePostgres($db, array $pending): void
    {
        $db->beginTransaction();
        try {
            foreach ($pending as $old => $new) {
                if (!$db->exec("ALTER TABLE " . $db->escapeColumn($old) . " RENAME TO " . $db->escapeColumn($new) . ";")) {
                    throw new \RuntimeException("Error renaming table $old to $new");
                }
                $this->renameSequences($old, $new);
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    private function renameMysql($db, array $pending): void
    {
        $parts = [];
        foreach ($pending as $old => $new) {
            $parts[] = $db->escapeColumn($old) . ' TO ' . $db->escapeColumn($new);
        }

        if (!$db->exec("RENAME TABLE " . implode(', ', $parts) . ";")) {
            throw new \RuntimeException('Error executing RENAME TABLE');
        }
    }

    private function renameSequences(string $oldTable, string $newTable): void
    {
        $rows = $this->db()->select(
            "SELECT relname FROM pg_class"
            . " WHERE relkind = 'S'"
            . " AND relname LIKE '" . $oldTable . "\\_%' ESCAPE '\\'"
        );

        foreach ($rows as $row) {
            $oldSeq = $row['relname'];
            $newSeq = str_replace($oldTable, $newTable, $oldSeq);
            if (!$this->db()->exec(
                "ALTER SEQUENCE " . $this->db()->escapeColumn($oldSeq)
                . " RENAME TO " . $this->db()->escapeColumn($newSeq) . ";"
            )) {
                throw new \RuntimeException("Error renaming sequence $oldSeq to $newSeq");
            }
        }
    }
}
