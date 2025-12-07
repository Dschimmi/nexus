<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use Tracy\Debugger;

/**
 * Verwaltet die globale Konfiguration der Anwendung.
 * Aggregiert Werte aus Environment (.env), Konfigurationsdatei (modules.json) und System-Defaults.
 * 
 * Architektur-Prinzip: Config and Code separation.
 */
class ConfigService
{
    private string $configFilePath;
    private array $settings = [];
    private array $defaults = [];

    /**
     * Erstellt den Service und initialisiert die Konfiguration.
     * 
     * @param string $projectDir Das Wurzelverzeichnis des Projekts.
     */
    public function __construct(string $projectDir)
    {
        $this->configFilePath = $projectDir . '/config/modules.json';
        
        // 1. System-Defaults aus Environment laden (12-Factor App)
        // Diese Werte sollten in der .env definiert sein.
        // Fallbacks dienen nur der technischen Stabilität (Crash-Prevention).
        $this->defaults = [
            // App Identity
            'app.name'   => $_ENV['APP_NAME'] ?? 'Exelor',
            'app.secret' => $_ENV['APP_SECRET'] ?? 'Warning:SetAppSecretInEnv!',
            
            // Session
            'session.lifetime'          => (int) ($_ENV['SESSION_LIFETIME'] ?? 1800),
            'session.absolute_lifetime' => (int) ($_ENV['SESSION_ABSOLUTE_LIFETIME'] ?? 43200),
            
            // Module Toggles (Standard: Aus, außer Compliance)
            'module_user_management'    => false,
            'module_site_search'        => false,
            'module_cookie_banner'      => true,
            'module_language_selection' => true,
        ];

        // 2. Persistierte Einstellungen laden (Override)
        $this->load();
    }

    /**
     * Liest einen Konfigurationswert.
     * Priorität: modules.json > .env/Defaults
     * 
     * @param string $key     Der Schlüssel.
     * @param mixed  $default Rückgabewert, falls Key unbekannt.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // 1. User-Config (Datei) hat Vorrang für Laufzeit-Einstellungen (Toggles)
        if (array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }
        
        // 2. Environment/Defaults für Infrastruktur-Werte
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
     * Setzt einen Wert und speichert ihn in modules.json.
     * Nutzung: Admin-Panel.
     */
    public function set(string $key, mixed $value): void
    {
        $this->settings[$key] = $value;
        $this->save();
    }

    /**
     * Lädt settings aus der JSON-Datei.
     */
    private function load(): void
    {
        if (file_exists($this->configFilePath)) {
            $content = file_get_contents($this->configFilePath);
            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $this->settings = $data;
                return;
            }
        }
        $this->settings = [];
    }

    /**
     * Persistiert die settings.
     */
    private function save(): void
    {
        try {
            // Wir speichern nur die Runtime-Settings (Toggles), nicht die Env-Werte!
            // Das verhindert, dass Secrets versehentlich in die JSON geschrieben werden.
            $data = json_encode($this->settings, JSON_PRETTY_PRINT);
            if ($data === false) {
                throw new \RuntimeException('Konfiguration konnte nicht kodiert werden.');
            }
            file_put_contents($this->configFilePath, $data);
        } catch (\Throwable $e) {
            Debugger::log($e, Debugger::ERROR);
        }
    }

    /**
     * Gibt alle effektiven Einstellungen zurück.
     */
    public function getAll(): array
    {
        return array_merge($this->defaults, $this->settings);
    }
}