<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\NumberTools;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\FormatoTicket;
use FacturaScripts\Plugins\PrintTicket\Lib\Ticket\Builder\AbstractTicketBuilder;

class PointOfSaleVoucher extends AbstractTicketBuilder
{

    /**
     * @var BusinessDocument
     */
    protected $document;

    protected $payments;


    public function __construct(BusinessDocument $document, array $payments = [], ?FormatoTicket $formato = null)
    {
        parent::__construct($formato);

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
     * Builds the ticket body
     */
    protected function buildBody(): void
    {
        $this->printer->textCentered($this->document->codigo);
        $fechacompleta = $this->document->fecha . ' ' . $this->document->hora;
        $this->printer->textCentered($fechacompleta);

        $this->printer->textCentered('CLIENTE: ' . $this->document->nombrecliente);
        $this->printer->lineSeparator();

        $this->printer->setFontBold();
        $this->printer->textCentered('ARTICULO');
        $this->printer->textColumns("PVP", "TOTAL");
        $this->printer->setFontBold(false);

        $this->printer->lineSeparator();

        $this->setBodyFontSize();
        foreach ($this->document->getLines() as $line) {
            if (self::PRICE_AFTER_TAX === $this->formato->formato_precio) {
                $printablePrice = $this->getPriceWithTax($line);
            } else {
                $printablePrice = $line->pvpunitario;
            }

            $printableTotal = $printablePrice * $line->cantidad;

            $this->printer->text("$line->cantidad x $line->referencia - $line->descripcion");

            if (self::PRICE_NO_PRICE !== $this->formato->formato_precio) {
                $this->printer->textColumns(
                    NumberTools::format($printablePrice),
                    NumberTools::format($printableTotal),
                    'R',
                    'R'
                );
            }

            $this->printer->lineBreak();
        }

        $this->printer->lineSeparator();

        if (self::PRICE_NO_PRICE === $this->formato->formato_precio) {
            return;
        }

        $this->printer->textColumns('BASE:', NumberTools::format($this->document->neto), 'L', 'R');
        $this->printer->textColumns('IVA:', NumberTools::format($this->document->totaliva), 'L', 'R');
        $this->printer->textColumns('TOTAL DEL DOCUMENTO:', NumberTools::format($this->document->total), 'L', 'R');
    }

    /**
     * Builds the ticket foot
     */
    protected function buildFooter(): void
    {
        $this->printer->lineSeparator();

        $paymentMethod = new FormaPago();
        foreach ($this->payments as $payment) {
            $paymentMethod->loadFromCode($payment['method']);
            $this->printer->textCentered($paymentMethod->descripcion);

            $this->printer->textColumns(
                'Recibido: ' . $payment['amount'],
                'Cambio: ' . $payment['change'], 'L', 'R');
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

    protected function getPriceWithTax($line): string
    {
        return floatval($line->pvpunitario) * (100 + floatval($line->iva)) / 100;
    }
}
