<?php

declare(strict_types=1);

namespace MrWo\Nexus\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Controller für statische Standardseiten wie Impressum und Datenschutz.
 */
class StaticPageController
{
    private Environment $twig;

    /**
     * @param Environment $twig Die Template-Engine.
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Zeigt das Impressum an.
     *
     * @return Response
     */
    public function imprint(): Response
    {
        return new Response($this->twig->render('imprint.html.twig'));
    }

    /**
     * Zeigt die Datenschutzerklärung an.
     *
     * @return Response
     */
    public function privacy(): Response
    {
        return new Response($this->twig->render('privacy.html.twig'));
    }
}