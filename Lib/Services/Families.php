<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Familia;

class Families
{
    /**
     * Returns families flagged as POS shortcuts for the root level of the
     * filter modal.
     *
     * Only families with pos_shortcut = true are surfaced. A shortcut is
     * shown at the root if:
     *  - it has no parent (true root), OR
     *  - its parent is not itself a family that contains shortcut
     *    children (i.e. the shortcut would not be reachable via drill-in).
     *
     * This guarantees:
     *  - Every visible shortcut is reachable from the root level.
     *  - No shortcut appears twice (root entry excludes those whose parent
     *    is also a shortcut-with-shortcut-children, because the parent
     *    already exposes them via drill-in).
     *
     * @return array Familia[]
     */
    public function getShortcutFamilies(): array
    {
        $momsOfShortcutChildren = array_column(
            Familia::all([Where::eq('pos_shortcut', true), Where::isNotNull('madre')]),
            'madre'
        );
        $momsOfShortcutChildren = array_values(array_unique(array_filter($momsOfShortcutChildren)));

        $where = [
            Where::eq('pos_shortcut', true),
            Where::sub([
                Where::isNull('madre'),
                Where::orNotIn('madre', $momsOfShortcutChildren),
            ]),
        ];

        return Familia::all($where, ['descripcion' => 'ASC']);
    }

    /**
     * Returns child families of a parent family that have pos_shortcut
     * enabled. Sibling families without the flag are intentionally hidden
     * from the navigator so only "show in POS" categories are exposed.
     *
     * @param string $codfamilia Parent family code
     * @return array Child families flagged as POS shortcuts
     */
    public function getChildFamilies(string $codfamilia): array
    {
        return Familia::all(
            [
                Where::eq('madre', $codfamilia),
                Where::eq('pos_shortcut', true),
            ],
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
     * - code == ''   returns the shortcut roots (top level), with orphan
     *                shortcut children of non-shortcut roots surfaced.
     * - code != ''   returns the family itself plus its pos_shortcut
     *                children (siblings without the flag are hidden).
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
