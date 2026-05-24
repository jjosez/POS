<?php

namespace FacturaScripts\Plugins\POS\Migration;

use FacturaScripts\Core\Template\MigrationClass;

class CreateSearchIndexes extends MigrationClass
{
    const MIGRATION_NAME = 'create_pos_search_indexes_v2.76.0';

    public function run(): void
    {
        $this->addIndexIfNotExists('variantes', 'idx_variantes_codbarras', 'codbarras');
        $this->addIndexIfNotExists('variantes', 'idx_variantes_producto', 'idproducto, referencia');
        $this->addIndexIfNotExists('productos_imagenes', 'idx_productos_imagenes_lookup', 'idproducto, referencia');
        $this->addIndexIfNotExists('stocks', 'idx_stocks_referencia_almacen', 'referencia, codalmacen, disponible');
    }
    private function addIndexIfNotExists(string $table, string $indexName, string $columns): void
    {
        if (false === $this->db()->tableExists($table)) {
            return;
        }
        $existingNames = array_map(
            fn(array $i): string => $i['name'],
            $this->db()->getAllIndexes($table)
        );
        if (in_array($indexName, $existingNames, true)) {
            return;
        }
        $this->db()->exec("CREATE INDEX $indexName ON $table ($columns)");
    }
}
