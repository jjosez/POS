<?php

namespace FacturaScripts\Plugins\POS;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Migrations;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Plugins\POS\Migration\RenameTables;

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
        Migrations::runPluginMigration(new RenameTables());
        Migrations::runPluginMigration(new Migration\CreateDefaultDraftStatuses());
        Migrations::runPluginMigration(new Migration\CreateSearchIndexes());

        $this->updateRefundColumns();
    }

    private function updateRefundColumns(): void
    {
        $database = new DataBase();
        $database->connect();

        $columns = [
            'pos_operations' => [
                'idoperacion_original' => 'INTEGER',
                'esdevolucion' => 'BOOLEAN NOT NULL DEFAULT false',
            ],
            'pos_terminals' => [
                'codserierect' => 'VARCHAR(6)',
            ],
        ];

        foreach ($columns as $table => $cols) {
            $existing = array_map(
                fn(array $c): string => $c['name'],
                $database->getColumns($table)
            );

            foreach ($cols as $name => $type) {
                if (false === in_array($name, $existing, true)) {
                    $database->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $name . ' ' . $type . ';');
                }
            }
        }
    }

    public function uninstall(): void
    {
    }
}
