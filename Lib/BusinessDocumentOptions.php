<?php 
namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Core\Model\PageOption;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * 
 */
class BusinessDocumentOptions
{
    private static $i18n;

    /**
     * 
     * @return array
     */
    private function getColumns($columns)
    {
        $keys = array_keys($columns);
        if (empty($keys)) {
            return [];
        }

        $key = $keys[0];
        return $columns[$key]->columns;
    }

    /**
     * Returns the data of lines to the view.
     *
     * @return string
     */
    public static function getLineData($user)
    {
        self::$i18n = new Translator;
        $data = [
            'headers' => [],
            'columns' => [],
            'rows' => []
        ];

        $columns = self::loadPageOptions($user);

        foreach (self::getColumns($columns) as $col) {
            $item = [
                'data' => $col->widget->fieldname,
                'type' => $col->widget->getType(),
            ];

            if ($item['type'] === 'number' || $item['type'] === 'money') {
                $item['type'] = 'numeric';
                $item['numericFormat'] = DivisaTools::gridMoneyFormat();
            } elseif ($item['type'] === 'autocomplete') {
                $item['source'] = $col->widget->getDataSource();
                $item['strict'] = false;
                $item['visibleRows'] = 5;
                $item['trimDropdown'] = false;
            }

            if (!$col->hidden()) {
                $data['columns'][] = $item;
                $data['headers'][] = self::$i18n->trans($col->title);
            }
        }

        return json_encode($data);
    }

    /**
     *
     * @param User|false $user
     */
    public function loadPageOptions($user)
    {
        $businessDocumentColumns = [];
        $modals = [];
        $rows = [];
        $pageOption = new PageOption();

        $orderby = ['nick' => 'ASC'];
        $where = [
            new DataBaseWhere('name', 'BusinessDocumentLine'),
            new DataBaseWhere('nick', $user->nick),
            new DataBaseWhere('nick', 'NULL', 'IS', 'OR'),
        ];;
        
        if (!$pageOption->loadFromCode('', $where, $orderby)) {
            $viewName = 'BusinessDocumentLine';
            VisualItemLoadEngine::installXML($viewName, $pageOption);
        }

        VisualItemLoadEngine::loadArray($businessDocumentColumns, $modals, $rows, $pageOption);
        
        return $businessDocumentColumns;
    }
}