<?php

namespace FacturaScripts\Plugins\POS\Migration;

use FacturaScripts\Core\Template\MigrationClass;
use RuntimeException;

class RemoveCustomerAccount extends MigrationClass
{
    const MIGRATION_NAME = 'remove_pos_customer_account_v2.79.0';

    public function run(): void
    {
        if ($this->db()->tableExists('pos_document_types')) {
            $columns = array_column($this->db()->getColumns('pos_document_types'), 'name');
            if (
                in_array('payment_policy', $columns, true)
                && false === $this->db()->exec(
                    "UPDATE pos_document_types SET payment_policy = 'required' WHERE payment_policy = 'customer-account'"
                )
            ) {
                throw new RuntimeException('Unable to migrate POS document payment policies.');
            }
        }

        if (false === $this->db()->tableExists('pos_operations')) {
            return;
        }

        $columns = array_column($this->db()->getColumns('pos_operations'), 'name');
        if (
            in_array('payment_policy', $columns, true)
            && false === $this->db()->exec(
                "UPDATE pos_operations SET payment_policy = 'required' WHERE payment_policy = 'customer-account'"
            )
        ) {
            throw new RuntimeException('Unable to migrate POS operation payment policies.');
        }
        if (in_array('customer_account_amount', $columns, true)) {
            if (false === $this->db()->exec('ALTER TABLE pos_operations DROP COLUMN customer_account_amount')) {
                throw new RuntimeException('Unable to remove the POS customer account column.');
            }
        }
    }
}
