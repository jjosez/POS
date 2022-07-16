<?php

namespace FacturaScripts\Plugins\POS\Controller;

class EditFieldOption extends \FacturaScripts\Core\Controller\EditPageOption
{
    public $backPage = 'EditTerminalPuntoVenta';

    protected function loadPageOptions()
    {
        parent::loadPageOptions();
        print_r(json_encode($this->model->columns));
    }

    /**
     * Save new configuration for view
     */
    protected function saveAction()
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return;
        }

        foreach ($this->model->columns as $key1 => $group) {
            if ($group['tag'] === 'column') {
                $name = $group['name'];
                $this->setColumnOption($this->model->columns[$key1], $name, 'title', false, false);
                $this->setColumnOption($this->model->columns[$key1], $name, 'display', false, false);
                $this->setColumnOption($this->model->columns[$key1], $name, 'level', false, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'readonly', true, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'decimal', true, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'numcolumns', false, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'order', false, true);
                 $this->setColumnOption($this->model->columns[$key1], $name, 'vercarrito', true, true);
                continue;
            }

            foreach ($group['children'] as $key2 => $col) {
                $name = $col['name'];
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'title', false, false);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'display', false, false);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'level', false, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'readonly', true, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'decimal', true, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'numcolumns', false, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'order', false, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'vercarrito', false, true);
            }
        }

        if ($this->model->save()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            $this->loadPageOptions();
            return;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
    }

    /**
     * @param array $column
     * @param string $name
     * @param string $key
     * @param bool $isWidget
     * @param bool $allowEmpty
     */
    private function setColumnOption(&$column, string $name, string $key, bool $isWidget, bool $allowEmpty)
    {
        $newValue = self::toolBox()::utils()::noHtml($this->request->request->get($name . '-' . $key));
        if ($isWidget) {
            if (!empty($newValue) || $allowEmpty) {
                $column['children'][0][$key] = $newValue;
            }
            return;
        }

        if (!empty($newValue) || $allowEmpty) {
            $column[$key] = $newValue;
        }
    }
}
