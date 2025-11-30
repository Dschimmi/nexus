<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use Tracy\Debugger;

/**
 * Zentraler Service für das Session-Management.
 * Setzt Sicherheitsvorgaben (Fingerprinting, Timeouts, Secure Cookies) technisch um.
 * Kennt keine Fachlogik (User, Rollen), nur Key-Value-Speicher.
 */
class SessionService
{
    private const SESSION_LIFETIME = 1800;   // 30 Minuten Inaktivität
    private const ABSOLUTE_LIFETIME = 28800; // 8 Stunden absolute Laufzeit (8 * 60 * 60)
    
    private const FINGERPRINT_KEY = '__fingerprint';
    private const LAST_ACTIVITY_KEY = '__last_activity';
    private const CREATED_AT_KEY = '__created_at';

    private bool $started = false;

    /**
     * Startet die Session sicher mit definierten Parametern.
     * Führt Fingerprinting und Timeout-Checks durch.
     */
    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            // Sicherheits-Flags setzen (PH 4.1.2)
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1'); // Voraussetzung: HTTPS aktiv (PH 5.2)
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');

            session_start();
        }

        $this->started = true;

        // Sicherheitsprüfungen durchführen
        $this->validateSession();
    }

    /**
     * Regeneriert die Session-ID.
     * MUSS bei jedem Login/Logout oder Rechteänderung aufgerufen werden (PH 9.1.1.6.1).
     */
    public function regenerate(): void
    {
        if (!$this->started) {
            $this->start();
        }
        // true = alte Session-Datei löschen
        session_regenerate_id(true);
    }

    /**
     * Setzt einen Wert in die Session.
     */
    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * Holt einen Wert aus der Session.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Entfernt einen Wert aus der Session.
     */
    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * Zerstört die komplette Session (Logout).
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            $_SESSION = [];
            $this->started = false;
        }
    }

    /**
     * Fügt eine "Flash-Message" hinzu (Nachricht, die nur für den nächsten Request gültig ist).
     * 
     * @param string $type Der Typ der Nachricht (z.B. 'success', 'error', 'info').
     * @param string $message Der Inhalt der Nachricht.
     */
    public function addFlash(string $type, string $message): void
    {
        $this->start();
        if (!isset($_SESSION['__flashes'])) {
            $_SESSION['__flashes'] = [];
        }
        $_SESSION['__flashes'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Gibt alle Flash-Messages zurück und löscht sie sofort aus der Session.
     * 
     * @return array Ein Array von Arrays [['type' => '...', 'message' => '...'], ...]
     */
    public function getFlashes(): array
    {
        $this->start();
        $flashes = $_SESSION['__flashes'] ?? [];
        
        // "Flash": Nach dem Lesen sofort löschen (Consume-once)
        unset($_SESSION['__flashes']);
        
        return $flashes;
    }

    /**
     * Interne Sicherheitsvalidierung (Fingerprint & Timeout).
     */
    private function validateSession(): void
    {
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // 1. IP-Präfix generieren (Anonymisierung für mobile Netze, z.B. nur die ersten 3 Blöcke bei IPv4)
        $ipPrefix = implode('.', array_slice(explode('.', $clientIp), 0, 3)); 
        
        $currentFingerprint = hash('sha256', $ipPrefix . $userAgent);
        $currentTime = time();

        // Prüfen, ob dies eine neue Session ist
        if (!isset($_SESSION[self::FINGERPRINT_KEY])) {
            // Initialisierung
            $_SESSION[self::FINGERPRINT_KEY] = $currentFingerprint;
            $_SESSION[self::LAST_ACTIVITY_KEY] = $currentTime;
            $_SESSION[self::CREATED_AT_KEY] = $currentTime; // Startzeitpunkt festhalten
            return;
        }

        // 2. Fingerprint Check
        if (!hash_equals($_SESSION[self::FINGERPRINT_KEY], $currentFingerprint)) {
            Debugger::log('Session fingerprint mismatch. Destroying session.', Debugger::WARNING);
            $this->destroy();
            $this->start(); 
            return;
        }

        // 3. Timeout Check (Inaktivität)
        if (($currentTime - $_SESSION[self::LAST_ACTIVITY_KEY]) > self::SESSION_LIFETIME) {
            $this->destroy();
            $this->start();
            return;
        }

        // 4. Absolute Timeout Check (8 Stunden)
        // Prüfen, ob der Erstellungszeitpunkt existiert (Fallback für alte Sessions)
        if (!isset($_SESSION[self::CREATED_AT_KEY])) {
             $_SESSION[self::CREATED_AT_KEY] = $currentTime;
        }

        if (($currentTime - $_SESSION[self::CREATED_AT_KEY]) > self::ABSOLUTE_LIFETIME) {
            $this->destroy();
            $this->start();
            return;
        }

        // Timestamp der letzten Aktivität aktualisieren
        $_SESSION[self::LAST_ACTIVITY_KEY] = $currentTime;
    }
}