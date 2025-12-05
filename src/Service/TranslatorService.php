<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Service für Übersetzungen (i18n).
 * Verwaltet die aktuelle Sprache und lädt Übersetzungen.
 */
class TranslatorService
{
    private array $translations = [];
    private string $currentLocale = 'de'; // Fallback
    private string $translationsDir;

    public function __construct(
        private SessionService $session, 
        string $projectDir
    ) {
        $this->translationsDir = $projectDir . '/translations';
        // Initial laden (Default 'de'), damit der Service sofort nutzbar ist
        $this->loadTranslations();
    }

    /**
     * Ermittelt die Sprache für den aktuellen Request und lädt die Texte.
     * Reihenfolge:
     * 1. URL-Parameter (?lang=en) -> Setzt Session
     * 2. Session (Attribute Bag)
     * 3. Browser-Header (Accept-Language) -> Optional (hier vereinfacht weggelassen)
     * 4. Default ('de')
     */
    public function initializeLocale(Request $request): void
    {
        // Zugriff auf den Attribute-Bag für User-Einstellungen
        $attributes = $this->session->getBag('attributes');

        // 1. URL-Switch
        $queryLocale = $request->query->get('lang');
        if ($queryLocale && in_array($queryLocale, ['de', 'en'])) {
            $this->currentLocale = $queryLocale;
            $attributes->set('locale', $queryLocale);
        } 
        // 2. Session Check
        elseif ($attributes->has('locale')) {
            $this->currentLocale = $attributes->get('locale');
        }

        // 3. Texte laden
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
            // Fallback auf 'de', falls Datei fehlt
            $pathDe = $this->translationsDir . '/de.php';
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