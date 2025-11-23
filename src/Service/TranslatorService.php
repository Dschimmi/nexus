<?php

namespace MrWo\Nexus\Service;

/**
 * Verwaltet das Laden und Abrufen von Übersetzungs-Strings basierend auf der Benutzersession.
 */
class TranslatorService
{
    /**
     * Die Standardsprache, die als Fallback verwendet wird.
     */
    private const DEFAULT_LOCALE = 'de';

    /**
     * @var array<string, string> Die geladenen Übersetzungs-Strings für die aktuelle Sprache.
     */
    private array $translations = [];

    /**
     * @var string Der Basispfad zum Verzeichnis mit den Übersetzungsdateien.
     */
    private string $translationsPath;

    /**
     * @var SessionService Der Service zur persistenten Speicherung der Sprache.
     */
    private SessionService $sessionService;

    /**
     * @param SessionService $sessionService Der zu injizierende Session-Service.
     */
    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
        $this->translationsPath = __DIR__ . '/../../translations';
        
        // Lade die in der Session gespeicherte Sprache, mit Fallback auf die Standardsprache.
        $currentLocale = $this->sessionService->get('locale', self::DEFAULT_LOCALE);
        $this->loadTranslations($currentLocale);
    }

    /**
     * Übersetzt einen gegebenen Schlüssel.
     *
     * @param string $key Der Übersetzungsschlüssel (z.B. 'welcome_message').
     * @return string Die Übersetzung oder der Schlüssel selbst, falls keine Übersetzung gefunden wurde.
     */
    public function translate(string $key): string
    {
        return $this->translations[$key] ?? $key;
    }

    /**
     * Ändert die aktuelle Sprache, speichert sie in der Session und lädt die neuen Übersetzungen.
     *
     * @param string $locale Der neue Sprachcode (z.B. 'en').
     */
    public function setLocale(string $locale): void
    {
        $this->sessionService->set('locale', $locale);
        $this->loadTranslations($locale);
    }

    /**
     * Lädt die Übersetzungsdatei für eine bestimmte Sprache.
     *
     * @param string $locale Der Sprachcode (z.B. 'de', 'en').
     */
    private function loadTranslations(string $locale): void
    {
        $filePath = "{$this->translationsPath}/{$locale}.php";

        if (file_exists($filePath)) {
            $this->translations = require $filePath;
        } else {
            // Wenn die Zielsprache nicht existiert, lade die Default-Sprache als sicheren Fallback.
            $this->translations = require "{$this->translationsPath}/" . self::DEFAULT_LOCALE . ".php";
        }
    }
}