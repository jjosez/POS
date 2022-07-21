<?php

namespace FacturaScripts\Plugins\POS\Lib;

class PointOfSaleFormColumn
{
    /**
     * @var string
     */
    public $fieldname;

    /**
     * @var bool
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
     * @var bool
     */
    public $eneabled;
    /**
     * @var string
     */
    public $name;

    /**
     * @param array $data
     */
    public function __construct(array $data, string $name)
    {
        $this->name = $name;
        $this->fieldname = $data['fieldname'];
        $this->readonly = $this->getBoolValue($data, 'readonly');
        $this->eneabled = $this->getBoolValue($data, 'eneabled');
        $this->decimals = $this->getIntValue($data, 'decimals');
        $this->type = $this->getType($data);
        $this->onCart = $this->getBoolValue($data, 'carrito');
    }

    private function getBoolValue($fields, $key)
    {
        return isset($fields[$key]) && filter_var($fields[$key], FILTER_VALIDATE_BOOLEAN);
    }

    private function getIntValue($fields, $key)
    {
        return $fields[$key] ?? 2;
    }

    private function getType($fields)
    {
        if (in_array($fields['type'], ['number', 'money', 'percentage'])) {
            return 'number';
        }

        return 'text';
    }
}
