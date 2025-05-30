<?php

namespace FacturaScripts\Plugins\POS\Lib;

enum PointOfSaleHook: string
{
    case OnClosingTicketPrinting = 'onClosingTicketPrinting';
    case OnSaleTicketPrinting = 'onSaleTicketPrinting';
    case OnDraftTicketPrinting = 'onDraftTicketPrinting';


    public static function isValid(string $hook): bool
    {
        return in_array($hook, array_column(self::cases(), 'value'), true);
    }

    public static function list(): array
    {
        return array_column(self::cases(), 'value');
    }
}
