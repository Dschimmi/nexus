<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\PageManagerService;
use MrWo\Nexus\Repository\PageRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Testet den PageManagerService.
 * Fokus: Zusammenspiel mit dem Repository und Sitemap-Generierung.
 */
class PageManagerServiceTest extends TestCase
{
    private $repositoryMock;
    private string $projectDir;
    private string $sitemapPath;

    protected function setUp(): void
    {
        // Temporäres Verzeichnis für Sitemap
        $this->projectDir = sys_get_temp_dir() . '/nexus_page_test_' . uniqid();
        mkdir($this->projectDir . '/public', 0777, true);
        $this->sitemapPath = $this->projectDir . '/public/sitemap.xml';

        // Repository Mock erstellen
        $this->repositoryMock = $this->createMock(PageRepositoryInterface::class);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->sitemapPath)) {
            unlink($this->sitemapPath);
        }
        if (is_dir($this->projectDir . '/public')) {
            rmdir($this->projectDir . '/public');
        }
        rmdir($this->projectDir);
    }

    /**
     * Testet, ob createPage das Repository aufruft und die Sitemap aktualisiert.
     */
    public function testCreatePageDelegatesToRepository(): void
    {
        // Erwartung: save() wird im Repository aufgerufen
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->with('test-slug', 'Titel', 'Inhalt');

        // Erwartung: findAll() wird für Sitemap aufgerufen
        $this->repositoryMock->method('findAll')->willReturn([
            ['slug' => 'test-slug']
        ]);

        $service = new PageManagerService($this->repositoryMock, $this->projectDir);
        $service->createPage('test-slug', 'Titel', 'Inhalt');

        // Prüfen, ob Sitemap erstellt wurde
        $this->assertFileExists($this->sitemapPath);
    }

    /**
     * Testet, ob deletePages das Repository aufruft.
     */
    public function testDeletePagesDelegatesToRepository(): void
    {
        // Erwartung: delete() wird 2x aufgerufen
        $this->repositoryMock->expects($this->exactly(2))
            ->method('delete');

        $service = new PageManagerService($this->repositoryMock, $this->projectDir);
        $service->deletePages(['slug1', 'slug2']);
    }

    /**
     * Testet die Validierung leerer Slugs.
     */
    public function testCreatePageThrowsExceptionOnEmptySlug(): void
    {
        $this->expectException(\RuntimeException::class);
        
        $service = new PageManagerService($this->repositoryMock, $this->projectDir);
        $service->createPage('', 'Titel', 'Content');
    }
}