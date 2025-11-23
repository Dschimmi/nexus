<?php

namespace MrWo\Nexus\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Controller für die Anzeige der Startseite.
 */
class HomepageController
{
    /**
     * @var Environment Die Twig Template-Engine.
     */
    private Environment $twig;

    /**
     * Der DI-Container wird diesen Konstruktor aufrufen und automatisch
     * eine Instanz des 'Twig\Environment'-Service übergeben.
     *
     * @param Environment $twig Die zu injizierende Twig-Umgebung.
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Behandelt die Anfrage für die Startseite und rendert das Twig-Template.
     *
     * @return Response Die HTTP-Antwort mit dem gerenderten HTML-Inhalt.
     */
    public function __invoke(): Response
    {
        // Test - wird der Fehler korrekt an Tracy weitergeleitet?
        //$a = 1 / 0;

        // Rendere das Template. Twig kennt die 'trans'-Funktion jetzt automatisch
        // durch die Konfiguration in der services.php.
        $content = $this->twig->render('homepage.html.twig', [
            'title' => 'Nexus Startseite',
        ]);
        
        return new Response($content);
    }
}