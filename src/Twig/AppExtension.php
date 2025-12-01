<?php

namespace MrWo\Nexus\Twig;

use MrWo\Nexus\Service\TranslatorService;
use MrWo\Nexus\Service\AssetService;
use MrWo\Nexus\Service\ConfigService;
use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\PageManagerService; // Neu
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
    private SessionService $session;
    private PageManagerService $pageManager; // Neu

    /**
     * @param TranslatorService  $translator    Der zu injizierende Translator-Service.
     * @param AssetService       $assetService  Der zu injizierende Asset-Service.
     * @param ConfigService      $configService Der zu injizierende Config-Service.
     * @param SessionService     $session       Der zu injizierende Session-Service.
     * @param PageManagerService $pageManager   Der zu injizierende PageManager-Service.
     */
    public function __construct(
        TranslatorService $translator,
        AssetService $assetService,
        ConfigService $configService,
        SessionService $session,
        PageManagerService $pageManager // Neu
    ) {
        $this->translator = $translator;
        $this->assetService = $assetService;
        $this->configService = $configService;
        $this->session = $session;
        $this->pageManager = $pageManager;
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
            
            // Prüft, ob ein Konfigurations-Modul aktiviert ist
            new TwigFunction('config', fn(string $key) => $this->configService->isEnabled($key)),

            // Holt Flash-Messages aus der Session und löscht sie dabei
            new TwigFunction('flashes', fn() => $this->session->getFlashes()),

            // Holt die Liste der Dummy-Seiten (Neu)
            new TwigFunction('get_dummy_pages', fn() => $this->pageManager->getPages()),
        ];
    }
}