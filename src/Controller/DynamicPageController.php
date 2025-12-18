<?php

declare(strict_types=1);

namespace MrWo\Nexus\Controller;

use MrWo\Nexus\Attribute\IsPublic;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Twig\Environment;

/**
 * Controller für dynamisch erstellte Dummy-Seiten.
 * Lädt HTML-Fragmente aus /public/pages/ und zeigt sie an.
 */
class DynamicPageController
{
    private Environment $twig;
    private string $pagesDir;

    /**
     * @param Environment $twig Die Template-Engine.
     * @param string $projectDir Das Wurzelverzeichnis des Projekts.
     */
    public function __construct(Environment $twig, string $projectDir)
    {
        $this->twig = $twig;
        $this->pagesDir = $projectDir . '/public/pages';
    }

    /**
     * Zeigt eine Dummy-Seite an.
     *
     * @param string $slug Der URL-Slug der Seite.
     * @return Response
     * @throws ResourceNotFoundException Wenn die Datei nicht existiert (löst 404 aus).
     */

    #[isPublic]
    public function show(string $slug): Response
    {
        // Sicherheit: Slug bereinigen (nur a-z, 0-9, -)
        // Slugifying
        // 1. Kleinbuchstaben
        $slug = strtolower($slug);
        // 2. Leerzeichen zu Bindestrichen
        $slug = str_replace(' ', '-', $slug);
        // 3. Alles andere (Sonderzeichen) entfernen
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        // 4. Doppelte Bindestriche entfernen (optional, aber sauber)
        $slug = preg_replace('/-+/', '-', $slug);
        // 5. Ränder säubern
        $slug = trim($slug, '-');

        // Filepath bauen
        $filepath = $this->pagesDir . '/' . $slug . '.html';

        if (!file_exists($filepath)) {
            // Löst im Kernel den 404-Block aus
            throw new ResourceNotFoundException("Dummy-Seite nicht gefunden: " . $slug);
        }

        $content = file_get_contents($filepath);
        
        // Titel aus dem HTML-Kommentar extrahieren (Format: <!-- TITLE: Mein Titel -->)
        // Fallback ist der Slug, falls kein Titel gefunden wird.
        $title = ucfirst($slug);
        if (preg_match('/<!-- TITLE: (.*?) -->/', $content, $matches)) {
            $title = $matches[1];
        }

        return new Response($this->twig->render('dynamic_page.html.twig', [
            'page_title' => $title,
            'page_content' => $content
        ]));
    }
}