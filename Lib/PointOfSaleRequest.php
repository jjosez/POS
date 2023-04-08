<?php

namespace FacturaScripts\Plugins\POS\Lib;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class PointOfSaleRequest
{
    const DEFAULT_ORDER = 'FacturaCliente';
    const ORDER_ON_HOLD = 'OperacionPausada';

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

    public function __construct(Request $request, bool $orderHold = false)
    {
        $this->request = $request->request;

        if ($orderHold) {
            $oldDocumentType = $this->request->get('tipo-documento', self::DEFAULT_ORDER);
            $this->request->set('generadocumento', $oldDocumentType);
            $this->request->set('tipo-documento', self::ORDER_ON_HOLD);
        }

        $this->setProductList();
        $this->setPaymentList();
        $this->setDocumentData();
    }

    protected function setProductList(): void
    {
        $lines = $this->request->get('lines', []);

        $this->productList = json_decode($lines, true);
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
        $payments = $this->request->get('payments');
        $this->paymentList = json_decode($payments, true);
    }

    /**
     * @return array
     */
    public function getDocumentData(): array
    {
        return $this->documentData ?? [];
    }

    /**
     * @return array
     */
    public function getProductList(): array
    {
        return $this->productList ?? [];
    }

    /**
     * @return array
     */
    public function getPaymentList(): array
    {
        return $this->paymentList ?? [];
    }

    /**
     * @return string
     */
    public function getDocumentType(): string
    {
        return empty($this->orderType) ? self::DEFAULT_ORDER : $this->orderType;
    }
}
