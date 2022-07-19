<?php

namespace FacturaScripts\Plugins\POS\Lib;

class PointOfSaleFormColumn
{
    /**
     * @var string
     */
    public $fieldname;

    /**
     * @var string
     */
    public $readonly;

    /**
     * @var string
     */
    public $type;

    /**
     * @var int
     */
    public $decimals;

    /**
     * @var bool
     */
    public $onCart;

    /**
     * @param array $data
     */
    public function __construct($data)
    {
        $this->fieldname = $data['fieldname'];
        $this->readonly = $this->getBoolValue($data, 'readonly');
        $this->decimals = $this->getIntValue($data, 'decimals');
        $this->type = $this->getType($data);
        $this->onCart = $this->getBoolValue($data, 'cart');
    }

    private function getBoolValue($fields, $key)
    {
        return isset($fields[$key]) && ($fields[$key] === 1);
    }

    private function getIntValue($fields, $key)
    {
        return $fields[$key] ?? 2;
    }

    private function getType($fields)
    {
        if ($fields['type'] === 'number' || $fields['type'] === 'money') {
            return 'number';
        }

        return 'text';
    }
}
