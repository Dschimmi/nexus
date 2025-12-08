<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use MrWo\Nexus\Repository\PageRepositoryInterface;
use RuntimeException;

/**
 * Service zur Verwaltung von Inhaltsseiten.
 * Koordiniert Datenzugriff (via Repository) und Zusatzaufgaben (Sitemap).
 */
class PageManagerService
{
    private string $sitemapPath;

    public function __construct(
        private PageRepositoryInterface $repository,
        string $projectDir
    ) {
        $this->sitemapPath = $projectDir . '/public/sitemap.xml';
    }

    /**
     * Erstellt oder aktualisiert eine Seite.
     */
    public function createPage(string $slug, string $title, string $content): void
    {
        if (empty($slug)) {
            throw new RuntimeException('Slug darf nicht leer sein.');
        }

        // Sanitization (Business Rule)
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));

        $this->repository->save($slug, $title, $content);
        $this->updateSitemap();
    }

    /**
     * Gibt eine Liste aller Seiten zurück.
     */
    public function getPages(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Löscht Seiten.
     */
    public function deletePages(array $slugs): int
    {
        $count = 0;
        foreach ($slugs as $slug) {
            $this->repository->delete($slug);
            $count++;
        }
        
        if ($count > 0) {
            $this->updateSitemap();
        }
        
        return $count;
    }

    /**
     * Findet eine Seite (für Frontend-Controller).
     */
    public function getPage(string $slug): ?array
    {
        return $this->repository->findBySlug($slug);
    }

    /**
     * Aktualisiert die sitemap.xml.
     */
    private function updateSitemap(): void
    {
        $pages = $this->repository->findAll();
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Statische Seiten
        $staticPages = ['', 'impressum', 'datenschutz', 'kontakt'];
        foreach ($staticPages as $p) {
            $xml .= '  <url><loc>https://exelor.de/' . $p . '</loc></url>' . PHP_EOL;
        }

        // Dynamische Seiten
        foreach ($pages as $page) {
            $xml .= '  <url><loc>https://exelor.de/' . $page['slug'] . '</loc></url>' . PHP_EOL;
        }

        $xml .= '</urlset>';
        
        file_put_contents($this->sitemapPath, $xml);
    }
}