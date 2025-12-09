<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service\Provider;

use MrWo\Nexus\Contract\TranslationProviderInterface;

/**
 * Lädt Übersetzungen aus lokalen PHP-Dateien.
 */
class PhpFileTranslationProvider implements TranslationProviderInterface
{
    public function __construct(
        private string $projectDir
    ) {}

    public function loadTranslations(string $locale): array
    {
        $path = $this->projectDir . '/translations/' . $locale . '.php';

        if (file_exists($path)) {
            return require $path;
        }

        // Fallback: Wenn 'en-US' angefordert, versuche 'en'
        // (Hier vereinfacht: Wir geben leer zurück, Service kümmert sich um Fallback)
        return [];
    }
}