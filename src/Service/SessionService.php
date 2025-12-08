<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use SessionHandlerInterface;
use Tracy\Debugger;

/**
 * Nexus Session Service 2.0 (Enterprise Edition).
 * 
 * Implementiert strikte Daten-Isolation via "Bags", gehärtete Sicherheit
 * (Fingerprinting, Locking, Migration) und abstrahierten Zugriff.
 * Konfiguriert sich dynamisch über den ConfigService und nutzt einen injizierten Handler.
 * 
 * @see PHN 4.1.2
 */
class SessionService
{
    /** @var string Interner Schlüssel für Metadaten (Zeitstempel, Fingerprint). */
    private const KEY_META = '__nexus_meta';
    
    /** @var bool Status-Flag, ob die Session gestartet wurde. */
    private bool $started = false;
    
    /** @var array Cache für geladene SessionBag-Objekte (Lazy Loading). */
    private array $bags = [];

    // Konfigurationswerte
    private int $sessionLifetime;
    private int $absoluteLifetime;
    private string $appSecret;
    private string $appName;

    /**
     * Erstellt den Service, lädt die Konfiguration und registriert den Handler.
     * 
     * @param ConfigService           $config  Der Konfigurations-Service.
     * @param SessionHandlerInterface $handler Der Speicher-Handler (File, Redis, etc.).
     */
    public function __construct(
        private ConfigService $config,
        private SessionHandlerInterface $handler
    ) {
        // Konfiguration laden
        $this->sessionLifetime = (int) $config->get('session.lifetime');
        $this->absoluteLifetime = (int) $config->get('session.absolute_lifetime');
        $this->appSecret = (string) $config->get('app.secret');
        $this->appName = (string) $config->get('app.name');

        // Handler registrieren (Mantis 0000020)
        // Wir setzen den Handler, bevor die Session startet.
        session_set_save_handler($this->handler, true);

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
        }
    }

    /**
     * Startet die Session (oder resumed eine bestehende).
     * Führt Sicherheitschecks (Fingerprint, Timeout) aus und initialisiert Cookies.
     * 
     * @return void
     */
    public function start(): void
    {
        if ($this->started) {
            return;
        }

        // Sicherheits-Flags setzen, falls noch keine Session läuft
        if (session_status() === PHP_SESSION_NONE) {
            // Mantis 0000016: Entropie serverseitig erhöhen
            ini_set('session.sid_length', '48');
            ini_set('session.sid_bits_per_character', '5');

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
     * @param string $name Name des Bags (z.B. 'attributes', 'security', 'flash').
     * @return SessionBag Das Bag-Objekt.
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
     * Aktualisiert den 'last_activity' Zeitstempel.
     * Muss am Ende des Requests aufgerufen werden.
     * 
     * @return void
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

        // Metadaten aktualisieren
        $_SESSION[self::KEY_META]['last_activity'] = time();

        // Schreiben & File-Lock freigeben
        session_write_close();
        $this->started = false;
    }

    /**
     * Regeneriert die Session-ID (Schutz gegen Session Fixation).
     * Muss zwingend nach Login/Logout aufgerufen werden!
     * 
     * @param bool $destroy Wenn true, werden alte Session-Daten gelöscht (neue leere Session).
     * @return void
     */
    public function migrate(bool $destroy = false): void
    {
        $this->start();
        session_regenerate_id($destroy);
    }

    /**
     * Zerstört die Session komplett (Logout).
     * Löscht Server-Daten und macht das Client-Cookie ungültig.
     * 
     * @return void
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
     * 
     * @param string $type    Der Typ (z.B. 'success', 'error').
     * @param string $message Der Inhalt.
     * @return void
     */
    public function addFlash(string $type, string $message): void
    {
        $this->getBag('flash')->add($type, $message);
    }

    /**
     * Shortcut: Holt alle Flash-Messages und leert den Bag (Auto-Expire).
     * 
     * @return array Array der Nachrichten.
     */
    public function getFlashes(): array
    {
        $bag = $this->getBag('flash');
        $flashes = $bag->all();
        $bag->clear();
        return $flashes;
    }

    // --- Interne Sicherheitslogik ---

    /**
     * Validiert die Session gegen Hijacking (Fingerprint) und Timeouts.
     * Bei Fehler wird die Session invalidiert.
     * 
     * @return void
     */
    private function validateSession(): void
    {
        $meta = $_SESSION[self::KEY_META] ?? [];
        $now = time();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Anonymisierter Fingerprint (IPv4 /24) zur Vermeidung von False-Positives
        $ipParts = explode('.', $ip);
        $anonymizedIp = (count($ipParts) === 4) ? implode('.', array_slice($ipParts, 0, 3)) . '.0' : $ip;
        
        // Salted Fingerprint mit App-Secret aus Config
        $fingerprint = hash('sha256', $anonymizedIp . $ua . $this->appSecret);

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

        // 2. Idle Timeout Check (Nutzt Config-Wert)
        if (($now - $meta['last_activity']) > $this->sessionLifetime) {
            $this->invalidate();
            return;
        }

        // 3. Absolute Timeout Check (Nutzt Config-Wert)
        if (($now - $meta['created_at']) > $this->absoluteLifetime) {
            $this->invalidate();
            return;
        }
    }

    /**
     * Konfiguriert die Cookie-Parameter für maximale Sicherheit.
     * Nutzt App-Namen und Secret aus der Config für Obfuscation.
     * 
     * @return void
     */
    private function configureCookieParams(): void
    {
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $prefix = $isHttps ? '__Host-' : '';

        // Dynamischer Name: __Host-Exelor-Hash
        $sessionName = $prefix . $this->appName . '-' . substr(hash('sha256', $this->appSecret), 0, 8);
        session_name($sessionName);
        
        session_set_cookie_params([
            'lifetime' => $this->sessionLifetime, // Synchron zum Idle-Timeout
            'path' => '/',
            'domain' => '', // Leer lassen für __Host- Kompatibilität
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}