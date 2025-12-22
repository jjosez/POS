<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Familia;

class Families
{
    /**
     * Returns parent families configured as POS shortcuts.
     */
    public function getParentFamilies(): array
    {
        $where = [
            Where::eq('pos_shortcut', true)
        ];

        return Familia::all($where);
    }

    /**
     * Returns child families of a parent family.
     *
     * @param string $codfamilia Parent family code
     * @return array Child families
     */
    public function getChildFamilies(string $codfamilia): array
    {
        $where = [
            Where::eq('madre', $codfamilia)
        ];

        return Familia::all($where);
    }

    /**
     * Loads a family by its code.
     *
     * @param string $codfamilia Family code
     * @return Familia|null Family object or null if not found
     */
    public function getFamilyByCode(string $codfamilia): ?Familia
    {
        $familia = new Familia();

        if ($familia->load($codfamilia)) {
            return $familia;
        }

        return null;
    }

    /**
     * Gets family hierarchy data for filter navigation.
     *
     * @param string $codfamilia Parent family code (empty for root)
     * @return array Array with 'madre' and 'children' keys
     */
    public function getFamilyHierarchy(string $codfamilia = ''): array
    {
        if (empty($codfamilia)) {
            return [
                'madre' => '',
                'children' => $this->getParentFamilies()
            ];
        }

        $familia = $this->getFamilyByCode($codfamilia);

        return [
            'madre' => $familia ?: '',
            'children' => $familia ? $this->getChildFamilies($codfamilia) : $this->getParentFamilies()
        ];
    }
}
