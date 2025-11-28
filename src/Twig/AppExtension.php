<?php

namespace MrWo\Nexus\Twig;

use MrWo\Nexus\Service\TranslatorService;
use MrWo\Nexus\Service\AssetService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Registriert benutzerdefinierte Funktionen und Filter für Twig.
 */
class AppExtension extends AbstractExtension
{
    private TranslatorService $translator;
    private AssetService $assetService;

    /**
     * @param TranslatorService $translator Der zu injizierende Translator-Service.
     * @param AssetService $assetService Der zu injizierende Asset-Service.
     */
    public function __construct(TranslatorService $translator, AssetService $assetService)
    {
        $this->translator = $translator;
        $this->assetService = $assetService;
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
            new TwigFunction('asset', fn(string $name) => $this->assetService->get($name)),
        ];
    }
}