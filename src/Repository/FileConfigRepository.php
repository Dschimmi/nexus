<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

use Tracy\Debugger;

/**
 * Implementierung des ConfigRepository basierend auf einer JSON-Datei.
 * Speichert Einstellungen in config/modules.json.
 */
class FileConfigRepository implements ConfigRepositoryInterface
{
    private string $filePath;

    /**
     * @param string $projectDir Das Wurzelverzeichnis des Projekts.
     */
    public function __construct(string $projectDir)
    {
        $this->filePath = $projectDir . '/config/modules.json';
    }

    /**
     * Lädt die Konfiguration aus der JSON-Datei.
     * Gibt ein leeres Array zurück, wenn die Datei fehlt oder fehlerhaft ist.
     */
    public function load(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        $data = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        return [];
    }

    /**
     * Speichert die Konfiguration in die JSON-Datei.
     * Protokolliert Fehler via Tracy.
     */
    public function save(array $settings): void
    {
        try {
            $data = json_encode($settings, JSON_PRETTY_PRINT);
            
            if ($data === false) {
                throw new \RuntimeException('Konfiguration konnte nicht kodiert werden.');
            }
            
            // Sicherstellen, dass das Verzeichnis existiert
            $dir = dirname($this->filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($this->filePath, $data);
            
        } catch (\Throwable $e) {
            Debugger::log($e, Debugger::ERROR);
        }
    }
}