<?php

namespace MrWo\Nexus\Twig;

use MrWo\Nexus\Service\TranslatorService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Registriert benutzerdefinierte Funktionen und Filter für Twig.
 */
class AppExtension extends AbstractExtension
{
    /**
     * @var TranslatorService Der Übersetzungs-Service.
     */
    private TranslatorService $translator;

    /**
     * @param TranslatorService $translator Der zu injizierende Translator-Service.
     */
    public function __construct(TranslatorService $translator)
    {
        $this->translator = $translator;
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
}