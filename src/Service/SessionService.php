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
    private const KEY_VERSION = '_version';
    
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
        private SessionHandlerInterface $handler,
        private SecurityLogger $logger
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
        $_SESSION[self::KEY_VERSION] = time();
        
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
        $oldId = session_id();
        session_regenerate_id($destroy);
        
        $this->logger->log('session_migration', [
            'destroy_old' => $destroy,
            'old_id_hash' => hash('sha256', $oldId)
        ]);
    }

    /**
     * Zerstört die Session komplett (Logout).
     * Löscht Server-Daten und macht das Client-Cookie ungültig.
     * 
     * @return void
     */
    public function invalidate(): void
    {
        $this->logger->log('session_invalidation');
        
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

    public function generateCsrfToken(string $tokenId): string
    {
        $token = bin2hex(random_bytes(32));
        $bag = $this->getBag('security');
        
        // Wir speichern Tokens gruppiert
        $tokens = $bag->get('csrf', []);
        $tokens[$tokenId] = $token;
        
        $bag->set('csrf', $tokens);
        
        return $token;
    }

    /**
     * Prüft, ob ein übermitteltes Token gültig ist.
     */
    public function isCsrfTokenValid(string $tokenId, ?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $bag = $this->getBag('security');
        $tokens = $bag->get('csrf', []);

        if (!isset($tokens[$tokenId])) {
            return false;
        }

        // Zeitkonstanter Vergleich gegen Timing-Attacks
        return hash_equals($tokens[$tokenId], $token);
    }

    // --- Interne Sicherheitslogik ---

    /**
     * Validiert die Session gegen Hijacking (Fingerprint) und Timeouts.
     * Bei Fehler wird die Session invalidiert.
     * 
     * @return void
     */
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
        $_SESSION[self::KEY_VERSION] = time();
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $anonymizedIp = $this->logger->anonymizeIp($ip);
        $browserSignature = $this->parseUserAgent($ua);
        
        // Salted Fingerprint mit App-Secret aus Config
        // Optional: Geo-Location wird aktuell weggelassen, da kein Service verfügbar.
        $fingerprint = hash('sha256', $anonymizedIp . $browserSignature . $this->appSecret);

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
           $this->logger->log('fingerprint_mismatch', [
                'expected' => $meta['fingerprint'],
                'actual' => $fingerprint
            ]);
            $this->invalidate();
            return;
        }

        // 2. Idle Timeout Check (Nutzt Config-Wert)
        if (($now - $meta['last_activity']) > $this->sessionLifetime) {
            $this->logger->log('session_timeout_idle'); 
            $this->invalidate();
            return;
        }

        // 3. Absolute Timeout Check (Nutzt Config-Wert)
        if (($now - $meta['created_at']) > $this->absoluteLifetime) {
            $this->logger->log('session_timeout_absolute');
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

    /**
     * Extrahiert Browser-Familie, Major-Version UND die OS-Plattform.
     * Erstellt eine Signature (z.B. "MacOS|Chrome/123") für den Fingerprint.
     * Vermeidet Invalidierung bei Minor-Updates.
     *
     * @param string $ua Der komplette User-Agent-String.
     * @return string Die kombinierte Signatur aus OS und Browser.
     */
    
    /**
     * Extrahiert Browser-Familie, Major-Version UND die OS-Plattform.
     * Erstellt eine Signature (z.B. "MacOS|Chrome/123") für den Fingerprint.
     * Vermeidet Invalidierung bei Minor-Updates.
     *
     * @param string $ua Der komplette User-Agent-String.
     * @return string Die kombinierte Signatur aus OS und Browser.
     */
    private function parseUserAgent(string $ua): string
    {
        // 1. OS-Plattform extrahieren
        $operatingSystem = 'UnknownOS';

        // Sucht nach Haupt-Plattform-Namen (Case-Insensitive)
        if (preg_match('/(Windows|Macintosh|Linux|Android|iPhone|iPad|Mac\sOS\sX)/i', $ua, $matches)) {
            // Normalisierung der erkannten Plattform-Namen
            $matchedOs = $matches[1];
            
            if (stripos($matchedOs, 'Win') !== false) {
                $operatingSystem = 'Windows';
            } elseif (stripos($matchedOs, 'Mac') !== false) {
                // Unterscheidet MacOS von iOS (iPhone/iPad)
                $operatingSystem = (stripos($matchedOs, 'iP') !== false) ? 'iOS' : 'MacOS';
            } elseif (stripos($matchedOs, 'Linux') !== false) {
                $operatingSystem = 'Linux';
            } elseif (stripos($matchedOs, 'Android') !== false) {
                $operatingSystem = 'Android';
            }
        }

        // 2. Browser-Familie und Major-Version extrahieren
        $browserSignature = 'UnknownBrowser';

        if (preg_match('#(Firefox|Chrome|Safari|Edge|OPR)/([0-9]+)#', $ua, $matches)) {
            $browserSignature = $matches[1] . '/' . $matches[2];
        } elseif (preg_match('#Trident/.*rv:([0-9]+)#', $ua, $matches)) {
            $browserSignature = 'IE/' . $matches[1];
        }

        // 3. Kombinierte Signatur: OS|Browser/MajorVersion
        return $operatingSystem . '|' . $browserSignature;
    }    
}
