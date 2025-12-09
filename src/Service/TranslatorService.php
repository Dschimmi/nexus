<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use MrWo\Nexus\Contract\TranslationProviderInterface;

/**
 * Zentraler Service für Übersetzungen (i18n).
 * Aggregiert Daten aus verschiedenen Providern.
 */
class TranslatorService
{
    private array $translations = [];
    private string $currentLocale = 'de';
    
    /** @var TranslationProviderInterface[] */
    private array $providers = [];

    public function __construct(
        private SessionService $session
    ) {
        // Sprache aus Session laden
        $attributes = $this->session->getBag('attributes');
        if ($attributes->has('locale')) {
            $this->currentLocale = $attributes->get('locale');
        }
    }

    /**
     * Fügt eine Übersetzungsquelle hinzu.
     */
    public function addProvider(TranslationProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        // Cache invalidieren oder sofort nachladen? 
        // Wir laden lazy beim ersten translate() oder explizit.
        $this->reload(); 
    }

    /**
     * Lädt alle Übersetzungen neu (aus allen Providern).
     */
    public function reload(): void
    {
        $this->translations = [];
        
        // Fallback Sprache ('de') zuerst laden
        foreach ($this->providers as $provider) {
            $this->translations = array_merge($this->translations, $provider->loadTranslations('de'));
        }

        // Aktuelle Sprache darüber mergen (überschreibt Defaults)
        if ($this->currentLocale !== 'de') {
            foreach ($this->providers as $provider) {
                $this->translations = array_merge($this->translations, $provider->loadTranslations($this->currentLocale));
            }
        }
    }

    public function translate(string $key, array $params = []): string
    {
        // Initial laden, falls noch nicht geschehen (und Provider da sind)
        if (empty($this->translations) && !empty($this->providers)) {
            $this->reload();
        }

        $text = $this->translations[$key] ?? $key;

        if (!empty($params)) {
            return strtr($text, $params);
        }

        return $text;
    }

    public function getLocale(): string
    {
        return $this->currentLocale;
    }
}