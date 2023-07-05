<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FormatoTicket;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Plugins\PrintTicket\Lib\Ticket\Builder\AbstractTicketBuilder;

class PointOfSaleClosingVoucher extends AbstractTicketBuilder
{
    protected $company;
    protected $session;

    public function __construct(SesionPuntoVenta $session, Empresa $company, ?FormatoTicket $formato = null)
    {
        parent::__construct($formato);

        $this->session = $session;
        $this->company = $company;

        $this->ticketType = 'Cashup';
    }

    protected function buildHeader(): void
    {
        $this->printer->lineBreak();

        $this->printer->lineSeparator('=');
        $this->printer->textCentered($this->company->nombrecorto);
        $this->printer->textCentered($this->company->direccion);

        if ($this->company->telefono1) {
            $this->printer->textCentered('TEL: ' . $this->company->telefono1);
        }
        if ($this->company->telefono2) {
            $this->printer->textCentered('TEL: ' . $this->company->telefono2);
        }

        $this->printer->textCentered($this->company->cifnif);
        $this->printer->lineSeparator('=');
    }

    protected function buildBody(): void
    {
        $this->printer->textCentered('CIERRE');
        $this->printer->textKeyValue('DESDE', $this->session->fechainicio);
        $this->printer->textKeyValue('HASTA', $this->session->fechafin);
        $this->printer->lineSeparator('=');

        $this->printer->textKeyValue('SALDO INICIAL', $this->session->saldoinicial);
        $this->printer->lineSeparator();

        $this->printer->textCentered('RESUMEN DE PAGOS');
        $this->printer->lineBreak();

        foreach ($this->session->getPaymentsAmount() as $payment) {
            $this->printer->textKeyValue(strtoupper($payment['descripcion']), $payment['total']);
        }

        $this->printer->lineSeparator('=');
        $this->printer->textKeyValue('TOTAL ESPERADO', $this->session->saldoesperado);
        $this->printer->textKeyValue('TOTAL CONTADO', $this->session->saldocontado);
    }

    protected function buildFooter(): void
    {
        $this->printer->lineBreak(3);
        $this->printer->textCentered('FIRMA');
    }

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
