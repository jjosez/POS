<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core\Session;

use FacturaScripts\Core\Session;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;

/**
 * Service for managing session state in PHP Session.
 * Handles caching and synchronization of session data for fast AJAX responses.
 */
class SessionStateManager
{
    private const POS_SESSION_ID = 'POS_SESSION_ID';
    private const POS_TERMINAL_ID = 'POS_TERMINAL_ID';
    private const POS_SESSION = 'POS_SESSION';
    private const POS_TERMINAL = 'POS_TERMINAL';
    private const POS_SESSION_IS_OPEN = 'POS_SESSION_IS_OPEN'; // Cache flag for fast checks

    /**
     * Warms up the session cache for fast AJAX validation.
     * Caches the isOpen flag to avoid DB queries on every request.
     */
    public function warmCache(SesionPuntoVenta $session, TerminalPuntoVenta $terminal): void
    {
        Session::set(self::POS_SESSION_ID, $session->idsesion);
        Session::set(self::POS_SESSION, $session);
        Session::set(self::POS_TERMINAL_ID, $terminal->idterminal);
        Session::set(self::POS_TERMINAL, $terminal);

        // Cache the open status for ultra-fast validation
        Session::set(self::POS_SESSION_IS_OPEN, $session->abierto && !empty($session->nickusuario));
    }

    /**
     * Ultra-fast check if the session is open (NO DB query).
     * Perfect for AJAX request validation.
     */
    public function isOpenCached(): bool
    {
        return (bool) Session::get(self::POS_SESSION_IS_OPEN, false);
    }

    /**
     * Gets cached session ID.
     */
    public function getSessionId(): ?string
    {
        return Session::get(self::POS_SESSION_ID);
    }

    /**
     * Gets cached terminal ID.
     */
    public function getTerminalId(): ?string
    {
        return Session::get(self::POS_TERMINAL_ID);
    }

    /**
     * Gets the cached session model.
     */
    public function getSessionModel(): ?SesionPuntoVenta
    {
        return Session::get(self::POS_SESSION);
    }

    /**
     * Gets cached terminal model.
     */
    public function getTerminalModel(): ?TerminalPuntoVenta
    {
        return Session::get(self::POS_TERMINAL);
    }

    /**
     * Synchronizes session state with the database and updates cache.
     * Call this after session modifications.
     */
    public function sync(SesionPuntoVenta $session): void
    {
        Session::set(self::POS_SESSION, $session);
        Session::set(self::POS_SESSION_IS_OPEN, $session->abierto && !empty($session->nickusuario));
    }

    /**
     * Clears all session cache.
     * Call this on session close.
     */
    public function clearCache(): void
    {
        Session::set(self::POS_SESSION_ID, null);
        Session::set(self::POS_TERMINAL_ID, null);
        Session::set(self::POS_SESSION, null);
        Session::set(self::POS_TERMINAL, null);
        Session::set(self::POS_SESSION_IS_OPEN, false);
    }

    /**
     * Checks if a cache exists.
     */
    public function hasCachedSession(): bool
    {
        return Session::get(self::POS_SESSION_ID) !== null;
    }
}
