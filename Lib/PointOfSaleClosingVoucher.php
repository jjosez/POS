<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Plugins\PrintTicket\Lib\Ticket\Builder\AbstractTicketBuilder;

class PointOfSaleClosingVoucher extends AbstractTicketBuilder
{
    protected $company;
    protected $session;

    public function __construct(SesionPuntoVenta $session, Empresa $company, int $width)
    {
        parent::__construct($width);

        $this->session = $session;
        $this->company = $company;

        $this->ticketType = 'Cashup';
    }

    protected function buildHeader(): void
    {
        $this->printer->lineBreak();

        $this->printer->lineSplitter('=');
        $this->printer->text($this->company->nombrecorto, true, true);
        $this->printer->bigText($this->company->direccion, true, true);

        if ($this->company->telefono1) {
            $this->printer->text('TEL: ' . $this->company->telefono1, true, true);
        }
        if ($this->company->telefono2) {
            $this->printer->text('TEL: ' . $this->company->telefono2, true, true);
        }

        $this->printer->text($this->company->cifnif, true, true);
        $this->printer->LineSplitter('=');
    }

    protected function buildBody(): void
    {
        $this->printer->text('CIERRE', true, true);
        $this->printer->keyValueText('DESDE', $this->session->fechainicio);
        $this->printer->keyValueText('HASTA', $this->session->fechafin);
        $this->printer->lineSplitter('=');

        $this->printer->keyValueText('SALDO INICIAL', $this->session->saldoinicial);
        $this->printer->lineSplitter();

        $this->printer->text('RESUMEN DE PAGOS', true, true);
        $this->printer->lineBreak();

        foreach ($this->session->getPagosTotales() as $payment) {
            $this->printer->keyValueText(strtoupper($payment['descripcion']), $payment['total']);
        }

        $this->printer->lineSplitter('=');
        $this->printer->keyValueText('TOTAL ESPERADO', $this->session->saldoesperado);
        $this->printer->keyValueText('TOTAL CONTADO', $this->session->saldocontado);
    }

    protected function buildFooter(): void
    {
        $this->printer->lineBreak(3);
        $this->printer->text('FIRMA', true, true);
    }

    public function getResult(): string
    {
        $this->buildHeader();
        $this->buildBody();
        $this->buildFooter();

        $this->printer->lineBreak(3);

        return $this->printer->output();
    }
}
