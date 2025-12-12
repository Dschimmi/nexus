<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use MrWo\Nexus\Repository\ConfigRepositoryInterface;

/**
 * Verwaltet die globale Konfiguration der Anwendung.
 * Aggregiert Werte aus Environment (.env), Repository (modules.json) und System-Defaults.
 */
class ConfigService
{
    private array $settings = [];
    private array $defaults = [];

    /**
     * @param ConfigRepositoryInterface $repository Das Speicher-Repository.
     */
    public function __construct(
        private ConfigRepositoryInterface $repository
    ) {
        // 1. System-Defaults aus Environment laden (12-Factor App)
        $this->defaults = [
            'app.name'   => $_ENV['APP_NAME'] ?? 'Exelor',
            'app.secret' => $_ENV['APP_SECRET'] ?? 'Warning:SetAppSecretInEnv!',
            
            'session.lifetime'          => (int) ($_ENV['SESSION_LIFETIME'] ?? 1800),
            'session.absolute_lifetime' => (int) ($_ENV['SESSION_ABSOLUTE_LIFETIME'] ?? 43200),
            'session.handler'           => $_ENV['SESSION_HANDLER'] ?? 'native',
            
            'module_user_management'    => false,
            'module_site_search'        => false,
            'module_cookie_banner'      => true,
            'module_language_selection' => true,

            // Rate Limiting Konfiguration (Ticket 34)
            'security.login_max_attempts'   => (int) ($_ENV['LOGIN_MAX_ATTEMPTS'] ?? 5),    // Max. Versuche pro Zeitfenster
            'security.login_lockout_time'   => (int) ($_ENV['LOGIN_LOCKOUT_TIME'] ?? 300),  // Sekunden der Sperrung (5 Minuten)
            'security.login_attempt_window' => (int) ($_ENV['LOGIN_ATTEMPT_WINDOW'] ?? 3600), // Sekunden für Zählfenster (1 Stunde)

            // Content Security Policy (Ticket 34)
            'security.content_security_policy' => $_ENV['CSP_POLICY'] ?? "default-src 'self'; style-src 'self' 'nonce-CSP_NONCE'; script-src 'self' 'nonce-CSP_NONCE'",

            'database.dsn'      => $_ENV['DB_DSN'] ?? null,
            'database.user'     => $_ENV['DB_USER'] ?? null,
            'database.password' => $_ENV['DB_PASSWORD'] ?? null,
        ];

        // 2. Persistierte Einstellungen laden
        $this->settings = $this->repository->load();
    }

    /**
     * Liest einen Konfigurationswert.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }
        
        if (array_key_exists($key, $this->defaults)) {
            return $this->defaults[$key];
        }

        return $default;
    }

    /**
     * Prüft ein Toggle (Boolean).
     */
    public function isEnabled(string $key): bool
    {
        return (bool) $this->get($key, false);
    }

    /**
     * Setzt einen Wert und speichert ihn via Repository.
     */
    public function set(string $key, mixed $value): void
    {
        $this->settings[$key] = $value;
        $this->repository->save($this->settings);
    }

    /**
     * Gibt alle effektiven Einstellungen zurück.
     */
    public function getAll(): array
    {
        return array_merge($this->defaults, $this->settings);
    }
}