<?php

namespace FacturaScripts\Plugins\POS\Lib;

class PointOfSaleTicketFormat
{
    protected const DEFAULT_ICON = 'fa-solid fa-receipt';
    protected const SALES_TYPE = 'sale';
    protected const CLOSING_TYPE = 'closing';

    protected string $ticketAction;
    protected ?string $ticketFormatCode;
    protected string $ticketIcon;
    protected string $ticketType;
    protected string $ticketTitle;
    protected ?string $redirectionUrl;

    /**
     * Constructor privado para evitar la creación de instancias fuera de la clase.
     */
    private function __construct(
        string $ticketAction,
        string $ticketTitle,
        string $ticketType,
        ?string $ticketIcon = self::DEFAULT_ICON,
        ?string $ticketFormatCode = null,
        ?string $redirectionUrl = null
    )
    {
        $this->ticketAction = $ticketAction;
        $this->ticketFormatCode = $ticketFormatCode;
        $this->ticketTitle = $ticketTitle;
        $this->ticketType = $ticketType;
        $this->ticketIcon = $ticketIcon;
        $this->redirectionUrl = $redirectionUrl;
    }

    /**
     * Crea un ticket de tipo venta.
     *
     * @param string $ticketAction Accion a ejecutar.
     * @param string $ticketTitle Título del ticket.
     * @param string $ticketIcon Ícono del ticket.
     *
     * @return PointOfSaleTicketFormat
     */
    public static function createSalesTicket(
        string $ticketAction,
        string $ticketTitle,
        ?string $ticketIcon = null,
        ?string $ticketFormatCode = null,
    ): PointOfSaleTicketFormat
    {
        $icon = $ticketIcon ?? self::DEFAULT_ICON;
        return new self($ticketAction, $ticketTitle, self::SALES_TYPE, $icon, $ticketFormatCode);
    }

    /**
     * Crea un ticket de tipo cierre.
     *
     * @param string $ticketAction Accion a ejecutar.
     * @param string $ticketTitle Título del ticket.
     * @param string|null $ticketIcon Ícono del ticket.
     * @return PointOfSaleTicketFormat
     */
    public static function createClosingTicket(
        string $ticketAction,
        string $ticketTitle,
        ?string $ticketIcon = null,
        ?string $ticketFormatCode = null,
    ): PointOfSaleTicketFormat
    {
        $icon = $ticketIcon ?? self::DEFAULT_ICON;
        return new self($ticketAction, $ticketTitle, self::CLOSING_TYPE, $icon, $ticketFormatCode);
    }

    /**
     * Crea un ticket de tipo personalizado.
     *
     * @param string $ticketAction Accion a ejecutar.
     * @param string $ticketType Tipo de ticket.
     * @param string $ticketTitle Título del ticket.
     * @param string $ticketIcon Ícono del ticket.
     * @param string|null $redirectionUrl Url de redirección si aplica.
     * @return PointOfSaleTicketFormat
     */
    public static function createCustomTicket(
        string $ticketAction,
        string $ticketType,
        string $ticketTitle,
        string $ticketIcon = self::DEFAULT_ICON,
        ?string $redirectionUrl = null
    ): PointOfSaleTicketFormat
    {
        return new self($ticketAction, $ticketTitle, $ticketType, $ticketIcon, $redirectionUrl);
    }

    /**
     * Metodo __toString para representar el formato de ticekt como cadena en formato JSON.
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'ticketAction' => $this->ticketAction,
            'ticketFormatCode' => $this->ticketFormatCode,
            'ticketIcon' => $this->ticketIcon,
            'ticketType' => $this->ticketType,
            'ticketTitle' => $this->ticketTitle,
            'redirectionUrl' => $this->redirectionUrl,
        ];
    }

    public function getTicketType(): string
    {
        return $this->ticketType;
    }
}
