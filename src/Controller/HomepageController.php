<?php

namespace MrWo\Nexus\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
/**
 * Controller für die Anzeige der Startseite.
 */
class HomepageController
{
    /**
     * Behandelt die Anfrage für die Startseite und rendert das Twig-Template.
     *
     * @return Response Die HTTP-Antwort mit dem gerenderten HTML-Inhalt.
     */
    public function __invoke(): Response
    {
        // 1. Definiere den Pfad zu unseren Templates
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');

        // 2. Initialisiere die Twig-Umgebung
        $twig = new Environment($loader);

        // 3. Rendere das Template anstatt rohes HTML zu schreiben
        $content = $twig->render('homepage.html.twig', [
            'title' => 'Nexus Startseite',
        ]);

        return new Response($content);
    }
}