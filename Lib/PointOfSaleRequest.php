<?php

namespace FacturaScripts\Plugins\POS\Lib;

//use FacturaScripts\Core\Internal\SubRequest;
//use FacturaScripts\Core\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class PointOfSaleRequest
{
    protected array $documentData;
    protected array $documentLinesData;
    protected array $paymentData;

    //protected SubRequest $request;
    protected ParameterBag $request;
    protected string $documentType;

    public function __construct(Request $request)
    {
        $this->request = $request->request;

        $this->setDocumentLinesData();
        $this->setPaymentData();
        $this->setDocumentData();
    }

    protected function setDocumentLinesData(): void
    {
        $lines = $this->request->get('lines', []);

        $this->documentLinesData = json_decode($lines, true);
    }

    protected function setDocumentData(): void
    {
        $data = $this->request->all();

        unset($data['action'], $data['lines'], $data['linesMap'], $data['objectRaw'], $data['payments']);

        $this->documentData = $data;
        $this->documentType = $this->request->get('tipo-documento');
    }

    protected function setPaymentData(): void
    {
        $payments = $this->request->get('payments', '');

        $this->paymentData = json_decode($payments, true) ?? [];
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
        return $this->documentType ?? 'FacturaCliente';
    }
}
