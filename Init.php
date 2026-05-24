<?php

namespace FacturaScripts\Plugins\POS;

use FacturaScripts\Core\Migrations;
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
        Migrations::runPluginMigration(new Migration\CreateDefaultDraftStatuses());
        Migrations::runPluginMigration(new Migration\CreateSearchIndexes());
    }

    public function uninstall(): void
    {
    }
}
