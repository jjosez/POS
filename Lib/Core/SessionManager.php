<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core;

use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\POS\Lib\Core\Session\CashBalanceManager;
use FacturaScripts\Plugins\POS\Lib\Core\Session\SessionStateManager;
use FacturaScripts\Plugins\POS\Lib\Core\Session\SessionStatus;

/**
 * Application Service for managing Point of Sale session lifecycle.
 * Handles session open/close and coordinates with state management.
 *
 * Performance optimized for AJAX requests:
 * - Uses SessionStateManager for cached validation (no DB queries)
 * - Lazy loading of session data
 */
class SessionManager
{
    private SesionPuntoVenta $session;
    private TerminalPuntoVenta $terminal;
    private User $user;
    private SessionStateManager $stateManager;
    private ?CashBalanceManager $balanceService = null;

    public function __construct(User $user, ?SessionStateManager $stateManager = null)
    {
        $this->user = $user;
        $this->terminal = new TerminalPuntoVenta();
        $this->session = new SesionPuntoVenta();
        $this->stateManager = $stateManager ?? new SessionStateManager();

        $this->loadSession($user->nick);
    }

    /**
     * Loads user session from database or cache.
     */
    protected function loadSession(string $nick): void
    {
        // Try to load from the cache first (fast)
        if ($this->stateManager->hasCachedSession()) {
            $cachedSession = $this->stateManager->getSessionModel();
            $cachedTerminal = $this->stateManager->getTerminalModel();

            if ($cachedSession && $cachedTerminal && $cachedSession->nickusuario === $nick) {
                $this->session = $cachedSession;
                $this->terminal = $cachedTerminal;
                return;
            }
        }

        // Load from the database (slower, but necessary on the first load)
        if ($this->session->getUserSession($nick)) {
            $this->loadTerminal($this->session->idterminal);
            $this->stateManager->warmCache($this->session, $this->terminal);
        }
    }

    /**
     * Loads terminal by ID.
     */
    protected function loadTerminal(string $code): bool
    {
        if (false === $this->terminal->load($code)) {
            Tools::log('POS')->warning('cash-register-not-found', ['terminal_id' => $code]);
            return false;
        }

        return true;
    }

    /**
     * Gets the appropriate view based on session status.
     * Fast operation - uses cached status.
     */
    public function getView(): string
    {
        return $this->isOpen() ? '/Block/POS/Main' : '/Block/POS/Login';
    }

    /**
     * Ultra-fast check if the session is open (uses cache, NO DB query).
     * Perfect for AJAX request validation.
     */
    public function isOpen(): bool
    {
        // Use cached value for speed
        if ($this->stateManager->isOpenCached()) {
            return true;
        }

        // Fallback to model check (should rarely happen)
        return $this->session->abierto && !empty($this->session->nickusuario);
    }

    /**
     * Gets session status as Value Object.
     */
    public function getStatus(): SessionStatus
    {
        return $this->isOpen() ? SessionStatus::open() : SessionStatus::closed();
    }

    /**
     * Opens a new POS session.
     */
    public function open(string $terminalId, float $initialAmount = 0.0): bool
    {
        if ($this->isOpen()) {
            Tools::log('POS')->info('till-session-already-opened', [
                '%userNickname%' => $this->user->nick
            ]);
            return false;
        }

        if (!$this->loadTerminal($terminalId)) {
            return false;
        }

        if ($this->session->open($this->terminal, $initialAmount, $this->user)) {
            // Warm cache for fast further requests
            $this->stateManager->warmCache($this->session, $this->terminal);

            Tools::log('POS')->info('till-session-opened', [
                '%terminalName%' => $this->terminal->nombre,
                '%userNickname%' => $this->user->nick,
            ]);

            Tools::log('POS')->info('cash-up-total', ['%amount%' => $initialAmount]);

            return true;
        }

        Tools::log('POS')->error('error-opening-pos-session');
        return false;
    }

    /**
     * Closes the current POS session.
     */
    public function close(array $coinsCount): bool
    {
        if (!$this->isOpen()) {
            Tools::log('POS')->info('till-session-not-opened');
            return false;
        }

        // Update counted balance before closing
        $balanceService = $this->getBalanceService();
        $balance = $balanceService->updateCountedBalance($coinsCount);

        if ($this->session->close($this->terminal, $coinsCount)) {
            // Clear cache
            $this->stateManager->clearCache();

            Tools::log('POS')->info('till-session-closed', [
                '%userNickname%' => $this->user->nick
            ]);

            Tools::log('POS')->info('cash-up-total', [
                '%amount%' => $balance->getCounted()
            ]);

            if ($balance->hasDiscrepancy()) {
                Tools::log('POS')->warning('cash-discrepancy-detected', [
                    '%difference%' => $balance->getDifference()
                ]);
            }

            return true;
        }

        Tools::log('POS')->error('error-closing-pos-session');
        return false;
    }

    /**
     * Gets the terminal (with optional loading by ID).
     */
    public function getTerminal(string $id = ''): TerminalPuntoVenta
    {
        if (!empty($id)) {
            $this->loadTerminal($id);
        }

        return $this->terminal;
    }

    /**
     * Gets all terminals from the user's company.
     */
    public function getTerminalsFromCompany(): array
    {
        return $this->terminal->getAvailable($this->user->idempresa);
    }

    /**
     * Gets the session model.
     */
    public function getSession(): SesionPuntoVenta
    {
        return $this->session;
    }

    /**
     * Updates the user associated with the session.
     */
    public function updateUser(User $user): bool
    {
        $this->user = $user;
        $this->session->nickusuario = $user->nick;

        if ($this->session->save()) {
            $this->stateManager->sync($this->session);
            return true;
        }

        return false;
    }

    /**
     * Gets the cash balance service (lazy loaded).
     */
    public function getBalanceService(): CashBalanceManager
    {
        if ($this->balanceService === null) {
            $this->balanceService = new CashBalanceManager($this->session, $this->stateManager);
        }

        return $this->balanceService;
    }

    // ========================================================================
    // Static methods for backward compatibility (deprecated, use instances)
    // ========================================================================

    /**
     * @deprecated Use SessionStateManager instance methods instead
     */
    public static function getSessionID(): ?string
    {
        return Session::get('POS_SESSION_ID');
    }

    /**
     * @deprecated Use SessionStateManager instance methods instead
     */
    public static function getSessionModel(): ?SesionPuntoVenta
    {
        return Session::get('POS_SESSION');
    }

    /**
     * @deprecated Use SessionStateManager instance methods instead
     */
    public static function getSessionNick(): string
    {
        return Session::user()->nick;
    }

    /**
     * @deprecated Use SessionStateManager instance methods instead
     */
    public static function getSessionTerminal(?string $terminalID = null): ?TerminalPuntoVenta
    {
        $terminalID = $terminalID ?: Session::get('POS_TERMINAL_ID');

        if (!$terminalID) {
            return null;
        }

        $terminal = new TerminalPuntoVenta();
        return $terminal->load($terminalID) ? $terminal : null;
    }
}
