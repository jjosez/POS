<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Lib\Ticket\Builder\SalesTicket;
use FacturaScripts\Dinamic\Model\FormatoTicket;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;

class PointOfSaleVoucher extends SalesTicket
{

    /**
     * @var SalesDocument
     */
    protected $document;


    /**
     * @var PagoPuntoVenta[]
     */
    protected $payments;


    public function __construct(SalesDocument $document, array $payments, ?FormatoTicket $formato = null)
    {
        parent::__construct($document, $formato);

        $this->document = $document;
        $this->payments = $payments;
        $this->ticketType = $document->modelClassName();
    }

    /**
     * Builds the ticket head
     */
    protected function buildHeader(): void
    {
        $company = $this->document->getCompany();

        $this->printer->lineBreak();
        $this->setTitleFontStyle();

        $this->printer->textCentered($company->nombrecorto);
        $this->printer->textCentered($company->direccion);
        $this->printer->textCentered($company->codpostal . ' ' . $company->ciudad);
        if ($company->telefono1) {
            $this->printer->textCentered('TEL: ' . $company->telefono1);
        }
        if ($company->telefono2) {
            $this->printer->textCentered('TEL: ' . $company->telefono2);
        }

        $this->printer->textCentered($company->cifnif);

        foreach ($this->getCustomLines('header') as $line) {
            $this->printer->textCentered($line);
        }

        $this->resetFontStyle();
        $this->printer->lineSeparator('=');
    }

    /**
     * Builds the ticket foot
     */
    protected function buildFooter(): void
    {
        $this->printer->lineSeparator();

        foreach ($this->payments as $payment) {
            $this->printer->textCentered($payment->descripcion());
            $this->printer->textKeyValue('Recibido: ' . $payment->cantidad, 'Cambio: ' . $payment->cambio);
        }

        $this->printer->lineBreak(2);

        foreach ($this->getCustomLines('footer') as $line) {
            $this->printer->textCentered($line);
        }

        $this->printer->lineBreak();
        $this->printer->barcode($this->document->codigo);
    }

    /**
     * @return string
     */
    public function getResult(): string
    {
        $this->buildHeader();
        $this->buildBody();
        $this->buildFooter();

        $this->printer->lineFeed(3);
        $this->printer->textCentered('.');

        return $this->printer->getBuffer();
    }
}
