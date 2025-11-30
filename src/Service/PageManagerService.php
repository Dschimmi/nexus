<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

/**
 * Service für das Management von statischen Dummy-Seiten und der Sitemap.
 * 
 * Dieser Service übernimmt das physische Erstellen der HTML-Dateien im
 * öffentlichen Verzeichnis und aktualisiert automatisch die sitemap.xml,
 * wenn neue Seiten hinzugefügt werden.
 */
class PageManagerService
{
    /**
     * @var string Der absolute Pfad zum Verzeichnis, in dem Dummy-Seiten gespeichert werden.
     */
    private string $pagesDir;

    /**
     * @var string Der absolute Pfad zum 'public'-Verzeichnis (für die sitemap.xml).
     */
    private string $publicDir;

    /**
     * Initialisiert den Service und stellt sicher, dass das Zielverzeichnis existiert.
     *
     * @param string $projectDir Das Wurzelverzeichnis des Projekts.
     */
    public function __construct(string $projectDir)
    {
        $this->publicDir = $projectDir . '/public';
        $this->pagesDir = $this->publicDir . '/pages';

        // Sicherstellen, dass das Pages-Verzeichnis existiert, sonst erstellen.
        if (!is_dir($this->pagesDir)) {
            mkdir($this->pagesDir, 0755, true);
        }
    }

    /**
     * Erstellt eine neue Dummy-Seite als HTML-Datei und aktualisiert die Sitemap.
     * 
     * Der Inhalt wird als einfaches HTML-Fragment gespeichert.
     * Der Titel wird als HTML-Kommentar in die erste Zeile geschrieben, 
     * um ihn später ggf. auslesen zu können.
     * 
     * @param string $slug Der URL-Slug (z.B. 'meine-seite'). Wird bereinigt.
     * @param string $title Der Titel der Seite (für interne Zwecke).
     * @param string $content Der HTML-Inhalt der Seite.
     * @throws \RuntimeException Wenn der Slug ungültig ist oder die Datei nicht geschrieben werden kann.
     * @return void
     */
    public function createPage(string $slug, string $title, string $content): void
    {
        // Sicherheit: Slug auf erlaubte Zeichen (a-z, 0-9, Bindestrich) reduzieren.
        // Dies verhindert Directory Traversal Angriffe.
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
        
        if (empty($slug)) {
            throw new \RuntimeException('Ungültiger Slug: Der Slug darf nicht leer sein.');
        }

        // Metadaten als Kommentar hinzufügen (Simple Storage Mechanismus)
        $fileContent = "<!-- TITLE: {$title} -->\n" . $content;
        $filepath = $this->pagesDir . '/' . $slug . '.html';

        if (file_put_contents($filepath, $fileContent) === false) {
            throw new \RuntimeException("Fehler: Konnte Datei {$filepath} nicht schreiben.");
        }

        // Sitemap sofort aktualisieren, damit die neue Seite von Suchmaschinen gefunden wird.
        $this->updateSitemap();
    }

    /**
     * Generiert die sitemap.xml neu basierend auf statischen Routen und vorhandenen Dummy-Pages.
     * 
     * Die Sitemap folgt dem Standard-Protokoll von sitemaps.org 0.9.
     * Statische Seiten haben eine höhere Priorität als generierte Dummy-Seiten.
     * 
     * @return void
     */
    public function updateSitemap(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Basis-URL ermitteln (Provisorisch über $_SERVER, in Prod idealerweise über Config)
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . $host;
        
        // 1. Statische Hauptseiten definieren (fest codiert)
        $staticPages = [
            '/' => '1.0',
            '/impressum' => '0.8',
            '/datenschutz' => '0.8'
        ];

        foreach ($staticPages as $path => $priority) {
            $xml .= $this->buildSitemapUrl($baseUrl . $path, $priority);
        }

        // 2. Dummy-Seiten aus dem /public/pages Verzeichnis scannen
        $files = scandir($this->pagesDir);
        
        if ($files !== false) {
            foreach ($files as $file) {
                // Navigations-Einträge (. und ..) überspringen
                if ($file === '.' || $file === '..') {
                    continue;
                }
                
                // Nur HTML-Dateien berücksichtigen
                if (pathinfo($file, PATHINFO_EXTENSION) === 'html') {
                    $slug = pathinfo($file, PATHINFO_FILENAME);
                    // Dummy-Seiten erhalten eine niedrigere Priorität
                    $xml .= $this->buildSitemapUrl($baseUrl . '/' . $slug, '0.5');
                }
            }
        }

        $xml .= '</urlset>';

        // XML-Datei schreiben
        file_put_contents($this->publicDir . '/sitemap.xml', $xml);
    }

    /**
     * Erstellt den XML-Block für eine einzelne URL in der Sitemap.
     *
     * @param string $loc Die vollständige URL der Seite.
     * @param string $priority Die Priorität der Seite (0.0 bis 1.0).
     * @return string Der formatierte XML-String für den <url>-Block.
     */
    private function buildSitemapUrl(string $loc, string $priority): string
    {
        // Das Datum der letzten Änderung wird auf "heute" gesetzt.
        $lastMod = date('Y-m-d');
        
        return sprintf(
            "\t<url>\n\t\t<loc>%s</loc>\n\t\t<lastmod>%s</lastmod>\n\t\t<changefreq>weekly</changefreq>\n\t\t<priority>%s</priority>\n\t</url>\n",
            htmlspecialchars($loc),
            $lastMod,
            $priority
        );
    }
}