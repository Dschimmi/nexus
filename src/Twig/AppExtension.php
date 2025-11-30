<?php

namespace MrWo\Nexus\Twig;

use MrWo\Nexus\Service\TranslatorService;
use MrWo\Nexus\Service\AssetService;
use MrWo\Nexus\Service\ConfigService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Registriert benutzerdefinierte Funktionen und Filter für Twig.
 * Stellt Verbindungen zu Core-Services her, um diese im Template nutzbar zu machen.
 */
class AppExtension extends AbstractExtension
{
    private TranslatorService $translator;
    private AssetService $assetService;
    private ConfigService $configService;

    /**
     * @param TranslatorService $translator Der zu injizierende Translator-Service.
     * @param AssetService $assetService Der zu injizierende Asset-Service.
     * @param ConfigService $configService Der zu injizierende Config-Service.
     */
    public function __construct(
        TranslatorService $translator,
        AssetService $assetService,
        ConfigService $configService
    ) {
        $this->translator = $translator;
        $this->assetService = $assetService;
        $this->configService = $configService;
    }

    /**
     * Deklariert die benutzerdefinierten Filter.
     *
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('trans', [$this->translator, 'translate']),
        ];
    }

    /**
     * Deklariert die benutzerdefinierten Funktionen.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            // Liefert den versionierten Pfad zu einem Asset
            new TwigFunction('asset', fn(string $name) => $this->assetService->get($name)),
            
            // Prüft, ob ein Konfigurations-Modul aktiviert ist (gibt true/false zurück)
            new TwigFunction('config', fn(string $key) => $this->configService->isEnabled($key)),
        ];
    }
}