<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Familia;

class Families
{
    /**
     * Returns root families flagged as POS shortcuts.
     * A root family is included if it is marked as shortcut itself OR
     * has a direct child marked as shortcut. This prevents shortcut
     * children from being hidden when their parent is not flagged.
     * Used by the root level of the filter modal.
     */
    public function getShortcutFamilies(): array
    {
        $shortcutChildrenCodes = array_column(
            Familia::all([Where::eq('pos_shortcut', true)]),
            'madre'
        );
        $shortcutChildrenCodes = array_values(array_unique(array_filter($shortcutChildrenCodes)));

        if (empty($shortcutChildrenCodes)) {
            $where = [
                Where::isNull('madre'),
                Where::eq('pos_shortcut', true),
            ];
            return Familia::all($where, ['descripcion' => 'ASC']);
        }

        $where = [
            Where::isNull('madre'),
            Where::sub([
                Where::eq('pos_shortcut', true),
                Where::orIn('codfamilia', $shortcutChildrenCodes),
            ]),
        ];

        return Familia::all($where, ['descripcion' => 'ASC']);
    }

    /**
     * Returns child families of a parent family.
     * Subfamilias siempre se muestran al navegar dentro de una familia,
     * independientemente del flag pos_shortcut.
     *
     * @param string $codfamilia Parent family code
     * @return array Child families
     */
    public function getChildFamilies(string $codfamilia): array
    {
        return Familia::all(
            [Where::eq('madre', $codfamilia)],
            ['descripcion' => 'ASC']
        );
    }

    /**
     * Loads a family by its code.
     */
    public function getFamilyByCode(string $codfamilia): ?Familia
    {
        if (empty($codfamilia)) {
            return null;
        }

        $familia = new Familia();

        if ($familia->load($codfamilia)) {
            return $familia;
        }

        return null;
    }

    /**
     * Gets family hierarchy data for filter navigation.
     *
     * - code == ''   returns the shortcut roots (top level).
     * - code != ''   returns the mother + all its children regardless of
     *                pos_shortcut so navigation is never blocked.
     *
     * @param string $code Parent family code ('' for root)
     * @return array ['madre' => array|null, 'children' => array]
     */
    public function getFamilyHierarchy(string $code = ''): array
    {
        if (empty($code)) {
            $children = $this->getShortcutFamilies();

            return [
                'madre' => null,
                'children' => array_map(
                    fn ($family) => $this->formatFamily($family, 0, true),
                    $children
                )
            ];
        }

        $family = $this->getFamilyByCode($code);
        $children = $family ? $this->getChildFamilies($code) : [];

        return [
            'madre' => $family ? $this->formatFamily($family, 0, (bool) $family->pos_shortcut) : null,
            'children' => array_map(
                fn ($child) => $this->formatFamily($child, ($family->madre ? 1 : 0) + 1, (bool) $child->pos_shortcut),
                $children
            )
        ];
    }

    /**
     * Formats a family object for frontend consumption.
     */
    private function formatFamily(Familia $family, int $level = 0, bool $isShortcut = false): array
    {
        $hasChildren = count($this->getChildFamilies($family->codfamilia)) > 0;

        return [
            'codfamilia' => $family->codfamilia,
            'descripcion' => $family->descripcion,
            'madre' => $family->madre,
            'thumbnail' => $family->shorcutImage(),
            'hasChildren' => $hasChildren,
            'isShortcut' => $isShortcut,
            'level' => $level
        ];
    }
}
