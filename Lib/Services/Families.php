<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Familia;

class Families
{
    /**
     * Returns parent families configured as POS shortcuts.
     * Returns only root families (without parent).
     */
    public function getParentFamilies(): array
    {
        $where = [
            Where::eq('pos_shortcut', true),
            Where::isNull('madre')
        ];

        return Familia::all($where);
    }

    /**
     * Returns child families of a parent family.
     * Only returns children that are marked as POS shortcuts.
     *
     * @param string $codfamilia Parent family code
     * @return array Child families
     */
    public function getChildFamilies(string $codfamilia): array
    {
        $where = [
            Where::eq('madre', $codfamilia),
            Where::eq('pos_shortcut', true)
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
     * @param string $code Parent family code (empty for root)
     * @return array Array with 'madre' and 'children' keys
     */
    public function getFamilyHierarchy(string $code = ''): array
    {
        if (empty($code)) {
            $children = $this->getParentFamilies();
            return [
                'madre' => null,
                'children' => array_map(callback: function ($family) {
                    return $this->formatFamily($family);
                }, array: $children)
            ];
        }

        $family = $this->getFamilyByCode($code);
        $children = $family ? $this->getChildFamilies($code) : [];

        return [
            'madre' => $family ? $this->formatFamily($family) : null,
            'children' => array_map(callback: function ($family) {
                return $this->formatFamily($family);
            }, array: $children)
        ];
    }

    /**
     * Formats a family object for frontend consumption.
     *
     * @param Familia $family Family to format
     * @return array Formatted family data
     */
    private function formatFamily(Familia $family): array
    {
        $hasChildren = count($this->getChildFamilies($family->codfamilia)) > 0;

        return [
            'codfamilia' => $family->codfamilia,
            'descripcion' => $family->descripcion,
            'madre' => $family->madre,
            'thumbnail' => $family->shorcutImage(),
            'hasChildren' => $hasChildren
        ];
    }
}
