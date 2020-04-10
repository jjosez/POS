<?php


namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

class CartSessionStorage
{
    public function get(string $id)
    {
        return isset($_SESSION['easycart'][$id]) ? $_SESSION['easycart'][$id] : serialize([]);
    }

    public function put(string $id, array $data)
    {
        $_SESSION['easycart'][$id] = $data;
    }

    public function remove($id)
    {
        unset($_SESSION['easycart'][$id]);
    }

    public function destroy()
    {
        unset($_SESSION['easycart']);
    }
}
