<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use Tracy\Debugger;

/**
 * Nexus Session Service 2.0 (Enterprise Edition).
 * 
 * Implementiert strikte Daten-Isolation via "Bags", gehärtete Sicherheit
 * (Fingerprinting, Locking, Migration) und abstrahierten Zugriff.
 * 
 * @see PHN 4.1.2
 */
class SessionService
{
    // Konfiguration (Timeouts in Sekunden)
    private const SESSION_LIFETIME = 1800;   // 30 Min Inaktivität
    private const ABSOLUTE_LIFETIME = 43200; // 12 Std Hard-Limit
    
    // Interne Keys (versteckt vor Bags)
    private const KEY_META = '__nexus_meta';
    
    private bool $started = false;
    
    // Cache für Bags (Lazy Loading)
    private array $bags = [];

    /**
     * Konstruktor. Prüft, ob PHP bereits eine Session gestartet hat.
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
        }
    }

    /**
     * Startet die Session (oder resumed sie).
     * Führt Sicherheitschecks (Fingerprint, Timeout) aus.
     */
    public function start(): void
    {
        if ($this->started) {
            return;
        }

        // Sicherheits-Flags setzen, falls noch keine Session läuft
        if (session_status() === PHP_SESSION_NONE) {
            $this->configureCookieParams();
            session_start();
        }

        $this->started = true;
        $this->validateSession();
    }

    /**
     * Zugriff auf einen isolierten Daten-Container ("Bag").
     * Lädt den Bag lazy aus der Session oder erstellt einen neuen.
     * 
     * @param string $name Name des Bags (z.B. 'attributes', 'security', 'flash')
     */
    public function getBag(string $name): SessionBag
    {
        $this->start();
        
        if (!isset($this->bags[$name])) {
            $data = $_SESSION[$name] ?? [];
            $this->bags[$name] = new SessionBag($name, $data);
        }
        
        return $this->bags[$name];
    }

    /**
     * Speichert alle Änderungen aus den Bags in die Session zurück.
     * Muss am Ende des Requests aufgerufen werden.
     */
    public function save(): void
    {
        if (!$this->started) {
            return;
        }

        // Alle Bags serialisieren
        foreach ($this->bags as $name => $bag) {
            $_SESSION[$name] = $bag->all();
        }

        // Metadaten aktualisieren (Last Activity)
        $_SESSION[self::KEY_META]['last_activity'] = time();

        // Schreiben & File-Lock freigeben
        session_write_close();
        $this->started = false;
    }

    /**
     * Regeneriert die Session-ID (Schutz gegen Session Fixation).
     * Muss zwingend nach Login/Logout aufgerufen werden!
     * 
     * @param bool $destroy Wenn true, werden alte Session-Daten gelöscht.
     */
    public function migrate(bool $destroy = false): void
    {
        $this->start();
        session_regenerate_id($destroy);
    }

    /**
     * Zerstört die Session komplett (Logout).
     * Löscht Server-Daten und Client-Cookie.
     */
    public function invalidate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        $_SESSION = [];
        $this->bags = [];
        $this->started = false;

        // Cookie löschen
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }

    /**
     * Shortcut: Fügt eine Flash-Message zum 'flash'-Bag hinzu.
     */
    public function addFlash(string $type, string $message): void
    {
        $this->getBag('flash')->add($type, $message);
    }

    /**
     * Shortcut: Holt alle Flash-Messages und leert den Bag (Auto-Expire).
     */
    public function getFlashes(): array
    {
        $bag = $this->getBag('flash');
        $flashes = $bag->all();
        $bag->clear();
        return $flashes;
    }

    // --- Interne Sicherheitslogik ---

    private function validateSession(): void
    {
        $meta = $_SESSION[self::KEY_META] ?? [];
        $now = time();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Anonymisierter Fingerprint (IPv4 /24) zur Vermeidung von False-Positives
        $ipParts = explode('.', $ip);
        $anonymizedIp = (count($ipParts) === 4) ? implode('.', array_slice($ipParts, 0, 3)) . '.0' : $ip;
        $fingerprint = hash('sha256', $anonymizedIp . $ua);

        // Neue Session initialisieren
        if (empty($meta)) {
            $_SESSION[self::KEY_META] = [
                'created_at' => $now,
                'last_activity' => $now,
                'fingerprint' => $fingerprint
            ];
            return;
        }

        // 1. Fingerprint Check (Hijacking Schutz)
        if (!hash_equals($meta['fingerprint'], $fingerprint)) {
            Debugger::log('Session Hijacking Attempt blocked.', Debugger::WARNING);
            $this->invalidate();
            return;
        }

        // 2. Idle Timeout Check
        if (($now - $meta['last_activity']) > self::SESSION_LIFETIME) {
            $this->invalidate();
            return;
        }

        // 3. Absolute Timeout Check (Hard Limit)
        if (($now - $meta['created_at']) > self::ABSOLUTE_LIFETIME) {
            $this->invalidate();
            return;
        }
    }

    private function configureCookieParams(): void
    {
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        
        session_set_cookie_params([
            'lifetime' => 0, // Bis Browser geschlossen wird
            'path' => '/',
            'domain' => '', // Current Domain
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}