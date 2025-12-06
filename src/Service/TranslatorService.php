<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Service für Übersetzungen (i18n).
 * Lädt die aktive Sprache direkt aus der Session beim Start.
 */
class TranslatorService
{
    private array $translations = [];
    private string $currentLocale = 'de'; // Default
    private string $translationsDir;

    public function __construct(
        private SessionService $session, 
        string $projectDir
    ) {
        $this->translationsDir = $projectDir . '/translations';
        
        // Sprache direkt aus Session laden (falls vorhanden)
        $attributes = $this->session->getBag('attributes');
        if ($attributes->has('locale')) {
            $this->currentLocale = $attributes->get('locale');
        }

        // Texte laden
        $this->loadTranslations();
    }

    /**
     * Lädt die Übersetzungen aus der PHP-Datei.
     */
    private function loadTranslations(): void
    {
        $path = $this->translationsDir . '/' . $this->currentLocale . '.php';

        if (file_exists($path)) {
            $this->translations = require $path;
        } else {
            // Fallback auf 'english', falls Datei fehlt
            $pathDe = $this->translationsDir . '/en.php';
            if (file_exists($pathDe)) {
                $this->translations = require $pathDe;
            }
        }
    }

    /**
     * Übersetzt einen Schlüssel.
     */
    public function translate(string $key, array $params = []): string
    {
        $text = $this->translations[$key] ?? $key;

        if (!empty($params)) {
            return strtr($text, $params);
        }

        return $text;
    }

    /**
     * Gibt die aktuell aktive Sprache zurück.
     */
    public function getLocale(): string
    {
        return $this->currentLocale;
    }
}