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
    protected $documentLinesData;

    /**
     * @var array
     */
    protected $paymentData;

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

        $this->setDocumentLinesData();
        $this->setPaymentData();
        $this->setDocumentData();
    }

    protected function setDocumentLinesData(): void
    {
        $lines = $this->request->get('lines', []);
        //$lines = $this->request->get('linesMap', []);

        $this->documentLinesData = json_decode($lines, true);
    }

    protected function setDocumentData(): void
    {
        $data = $this->request->all();

        unset($data['action'], $data['lines'], $data['linesMap'], $data['objectRaw'],$data['payments']);

        $this->documentData = $data;
        $this->orderType = $this->request->get('tipo-documento', '');

        /*$data = $this->request->get('document', '{}');
        $this->documentData = json_decode($data, true);
        $this->orderType =$this->documentData['tipo-documento'];*/
    }

    protected function setPaymentData(): void
    {
        $payments = $this->request->get('payments', '');

        $this->paymentData = json_decode($payments, true);
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
    public function getDocumentLinesData(): array
    {
        return $this->documentLinesData ?? [];
    }

    /**
     * @return array
     */
    public function getPaymentData(): array
    {
        return $this->paymentData ?? [];
    }

    /**
     * @return string
     */
    public function getDocumentType(): string
    {
        return empty($this->orderType) ? self::DEFAULT_ORDER : $this->orderType;
    }
}
