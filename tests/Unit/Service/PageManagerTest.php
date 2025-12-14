<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Application\Page\PageManager;
use MrWo\Nexus\Domain\Page\PageRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Testet die Seiten-Verwaltung (Application Service).
 * Prüft Logik (Slug-Sanitization, Sitemap-Update) unabhängig von der Persistenz.
 */
class PageManagerTest extends TestCase
{
    private $repositoryMock;
    private $pageManager;
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir();
        $this->repositoryMock = $this->createMock(PageRepositoryInterface::class);

        $this->pageManager = new PageManager(
            $this->repositoryMock,
            $this->projectDir
        );
    }

    public function testCreatePageSanitizesSlug(): void
    {
        // Expect: Save wird mit bereinigtem Slug aufgerufen
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->with('hallo-welt', 'Titel', 'Content');

        $this->pageManager->createPage('Hallo Welt!', 'Titel', 'Content');
    }

    public function testCreatePageUpdatesSitemap(): void
    {
        // Sitemap Datei muss erstellt/aktualisiert werden
        $sitemapPath = $this->projectDir . '/public/sitemap.xml';
        if (!is_dir(dirname($sitemapPath))) mkdir(dirname($sitemapPath), 0777, true);
        
        $this->repositoryMock->method('findAll')->willReturn([
            ['slug' => 'test', 'title' => 'Test']
        ]);

        $this->pageManager->createPage('test', 'Test', '...');

        $this->assertFileExists($sitemapPath);
        $content = file_get_contents($sitemapPath);
        $this->assertStringContainsString('<loc>https://exelor.de/test</loc>', $content);
    }
}