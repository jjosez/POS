<?php

namespace FacturaScripts\Plugins\POS\Lib\POS\Sales;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class OrderRequest
{
    /**
     * @var array
     */
    protected $documentData;

    /**
     * @var array
     */
    protected $productList;

    /**
     * @var array
     */
    protected $paymentList;

    /**
     * @var ParameterBag
     */
    protected $request;

    /**
     * @var string
     */
    protected $orderType;

    public function __construct(Request $request)
    {
        $this->request = $request->request;

        $this->setProductList();
        $this->setDocumentData();
        $this->setPaymentList();
    }

    protected function setProductList(): void
    {
        $action = $this->request->get('action');
        $lines = $this->request->get('lines', []);

        if ('transaction-recalculate' !== $action) {
            $this->productList = json_decode($lines, true);
            return;
        }

        $this->productList = $lines;
    }

    protected function setDocumentData(): void
    {
        $data = $this->request->all();

        unset($data['action'], $data['lines'], $data['payments']);

        $this->documentData = $data;
        $this->orderType = $this->request->get('tipo-documento', '');
    }

    protected function setPaymentList(): void
    {
        $action = $this->request->get('action');
        $payments = $this->request->get('payments');

        if ('transaction-recalculate' !== $action) {
            $this->paymentList[] = json_decode($payments, true);
            return;
        }

        $this->paymentList[] = $payments ?? [];
    }

    /**
     * @return array
     */
    public function getDocumentData(): array
    {
        return $this->documentData;
    }

    /**
     * @return array
     */
    public function getProductList(): array
    {
        return $this->productList;
    }

    /**
     * @return array
     */
    public function getPaymentList(): array
    {
        return $this->paymentList;
    }

    /**
     * @return string
     */
    public function getOrderType(string $default): string
    {
        return empty($this->orderType) ? $default : $this->orderType;
    }
}
