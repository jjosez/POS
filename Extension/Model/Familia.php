<?php

namespace FacturaScripts\Plugins\POS\Extension\Model;

use Closure;
use FacturaScripts\Core\Model\AttachedFile;

/**
 * @property $pos_idlogo
 */
class Familia
{
    public function shorcutImage(): Closure
    {
        return function () {
            $file = new AttachedFile();

            if ($file->loadFromCode($this->pos_idlogo)) {
                return $file->url('download-permanent');
            }

            return '';
        };
    }

}
