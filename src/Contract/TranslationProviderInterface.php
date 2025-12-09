<?php

declare(strict_types=1);

namespace MrWo\Nexus\Contract;

/**
 * Schnittstelle für Quellen von Übersetzungen.
 * Ermöglicht die Anbindung verschiedener Datenquellen (PHP-Dateien, DB, API).
 */
interface TranslationProviderInterface
{
    /**
     * Lädt Übersetzungen für eine bestimmte Sprache.
     * 
     * @param string $locale Der Sprachcode (z.B. 'de', 'en').
     * @return array Assoziatives Array mit Übersetzungen ('key' => 'value').
     */
    public function loadTranslations(string $locale): array;
}