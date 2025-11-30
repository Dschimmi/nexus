<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use Tracy\Debugger;

/**
 * Verwaltet die globale Konfiguration und Feature-Toggles der Anwendung.
 * Speichert Einstellungen in einer JSON-Datei, um sie über das Admin-Panel persistieren zu können.
 */
class ConfigService
{
    /**
     * @var string Der absolute Pfad zur Konfigurationsdatei (z.B. modules.json).
     */
    private string $configFilePath;

    /**
     * @var array Die aktuell geladenen Einstellungen.
     */
    private array $settings = [];

    /**
     * @var array Standardwerte für Module, falls keine Konfigurationsdatei existiert.
     *            Standardmäßig sind optionale Module deaktiviert, Compliance-Module aktiviert.
     */
    private array $defaults = [
        'module_user_management' => false,    // User-Login/Registrierung
        'module_site_search' => false,        // Suchfeld im Header
        'module_cookie_banner' => true,       // DSGVO-Banner
        'module_language_selection' => true,  // Sprachwahl
    ];

    /**
     * @param string $projectDir Das Wurzelverzeichnis des Projekts.
     */
    public function __construct(string $projectDir)
    {
        // Wir speichern die Konfiguration im config-Ordner
        $this->configFilePath = $projectDir . '/config/modules.json';
        $this->load();
    }

    /**
     * Prüft, ob ein bestimmtes Modul oder eine Einstellung aktiviert ist.
     *
     * @param string $key Der Schlüssel der Einstellung (z.B. 'module_site_search').
     * @return bool
     */
    public function isEnabled(string $key): bool
    {
        return (bool) ($this->settings[$key] ?? $this->defaults[$key] ?? false);
    }

    /**
     * Setzt einen Wert und speichert die Konfiguration sofort.
     *
     * @param string $key Der Schlüssel.
     * @param mixed $value Der Wert (meist bool).
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->settings[$key] = $value;
        $this->save();
    }

    /**
     * Lädt die Konfiguration aus der JSON-Datei.
     *
     * @return void
     */
    private function load(): void
    {
        if (file_exists($this->configFilePath)) {
            $content = file_get_contents($this->configFilePath);
            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                // Wir mergen die geladenen Daten mit den Defaults, um sicherzustellen,
                // dass neue Keys in Zukunft auch existieren.
                $this->settings = array_merge($this->defaults, $data);
                return;
            }
        }

        // Fallback auf Defaults, wenn Datei nicht existiert oder fehlerhaft ist
        $this->settings = $this->defaults;
    }

    /**
     * Speichert die aktuellen Einstellungen in die JSON-Datei.
     *
     * @return void
     */
    private function save(): void
    {
        try {
            $data = json_encode($this->settings, JSON_PRETTY_PRINT);
            if ($data === false) {
                throw new \RuntimeException('Konfiguration konnte nicht kodiert werden.');
            }
            file_put_contents($this->configFilePath, $data);
        } catch (\Throwable $e) {
            // Fehler beim Speichern loggen (z.B. Berechtigungsprobleme)
            Debugger::log($e, Debugger::ERROR);
        }
    }

    /**
     * Gibt alle Einstellungen zurück (für das Admin-Formular).
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->settings;
    }
}