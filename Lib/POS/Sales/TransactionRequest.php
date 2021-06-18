<?php

namespace FacturaScripts\Plugins\POS\Lib\POS\Sales;

use Symfony\Component\HttpFoundation\Request;

class TransactionRequest
{
    /**
     * @var array
     */
    protected $documentData;

    /**
     * @var array
     */
    protected $lines;

    /**
     * @var array
     */
    protected $paymentsData;

    /**
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    protected $request;

    public function __construct($request)
    {
        $this->request = $request->request;

        $this->setLinesData();
        $this->setDocumentData();
        $this->setPaymentsData();

        print_r($this->getLines());
    }

    protected function setLinesData(): void
    {
        $action = $this->request->get('action');
        $lines = $this->request->get('lines', []);

        if ('transaction-recalculate' !== $action) {
            $this->lines = json_decode($lines, true);
            return;
        }

        $this->lines = $lines;
    }

    protected function setDocumentData(): void
    {
        $data = $this->request->all();

        unset($data['action'], $data['lines'], $data['payments']);

        $this->documentData = $data;
    }

    protected function setPaymentsData(): void
    {
        $action = $this->request->get('action');
        $payments = $this->request->get('payments');

        if ('transaction-recalculate' !== $action) {
            $this->paymentsData = json_decode($payments, true);
            return;
        }

        $this->linesData = $paymentsData ?? [];
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
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * @return array
     */
    public function getPaymentsData(): array
    {
        return $this->paymentsData;
    }
}