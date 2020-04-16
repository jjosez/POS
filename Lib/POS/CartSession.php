<?php


namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

use Riesenia\Cart\Cart;

class CartSession extends Cart
{
    private $id;
    private $session;

    public function __construct(string $id, CartSessionStorage $session, array $context = [], bool $pricesWithVat = true, int $roundingDecimals = 2)
    {
        $this->session = $session;
        $this->id = $id;

        parent::__construct($context, $pricesWithVat, $roundingDecimals);
    }

    protected function _cartModified()
    {
        return parent::_cartModified();
        $this->session->put($this->id, $this->getItems());
    }

    protected function remove()
    {
        $this->session-remove($this->id);
    }
}