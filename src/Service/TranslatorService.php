<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

/**
 * Service für Übersetzungen (i18n).
 * Lädt Sprachdateien und ersetzt Platzhalter.
 */
class TranslatorService
{
    private array $translations = [];
    private string $currentLocale = 'de'; // Default Sprache
    private string $translationsDir;

    /**
     * @param SessionService $session    (Aktuell noch ungenutzt, für spätere User-Sprache)
     * @param string         $projectDir Der absolute Pfad zum Projektverzeichnis.
     */
    public function __construct(SessionService $session, string $projectDir)
    {
        // Später: Locale aus Session laden ($session->get('locale'))
        $this->translationsDir = $projectDir . '/translations';
        $this->loadTranslations();
    }

    /**
     * Lädt die Übersetzungen aus der PHP-Datei.
     */
    private function loadTranslations(): void
    {
        $path = $this->translationsDir . '/' . $this->currentLocale . '.php';

        // --- DEBUG START (Temporär) ---
        // Wir geben den Pfad und das Ergebnis der Prüfung aus.
        fwrite(STDERR, "\n[DEBUG] Prüfe Pfad: " . $path . "\n");
        fwrite(STDERR, "[DEBUG] file_exists: " . (file_exists($path) ? 'JA' : 'NEIN') . "\n");
        // --- DEBUG END ---

        if (file_exists($path)) {
            $this->translations = require $path;
        }
    }

    /**
     * Übersetzt einen Schlüssel und ersetzt optionale Platzhalter.
     * 
     * @param string $key Der Übersetzungsschlüssel.
     * @param array $params Parameter zum Ersetzen (z.B. ['%name%' => 'Max']).
     * @return string Der übersetzte Text.
     */
    public function translate(string $key, array $params = []): string
    {
        // 1. Text holen (oder Key zurückgeben, falls nicht gefunden)
        $text = $this->translations[$key] ?? $key;

        // 2. Platzhalter ersetzen, falls Parameter übergeben wurden
        if (!empty($params)) {
            // strtr ist effizienter als str_replace für mehrere Ersetzungen
            return strtr($text, $params);
        }

        return $text;
    }
}