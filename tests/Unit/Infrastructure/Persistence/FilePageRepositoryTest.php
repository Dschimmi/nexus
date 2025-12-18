<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Persistence;

use MrWo\Nexus\Infrastructure\Persistence\FilePageRepository;
use PHPUnit\Framework\TestCase;

/**
 * Testet das Datei-basierte PageRepository.
 * 
 * Validiert:
 * - Speichern und Laden von HTML-Dateien.
 * - Auflisten aller Seiten.
 * - Löschen von Seiten.
 * - Automatische Erstellung des Verzeichnisses.
 */
class FilePageRepositoryTest extends TestCase
{
    private string $projectDir;
    private FilePageRepository $repo;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/nexus_pages_' . uniqid();
        $this->repo = new FilePageRepository($this->projectDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir . '/public/pages')) {
            array_map('unlink', glob($this->projectDir . '/public/pages/*'));
            rmdir($this->projectDir . '/public/pages');
        }
        if (is_dir($this->projectDir . '/public')) {
            rmdir($this->projectDir . '/public');
        }
        if (is_dir($this->projectDir)) {
            rmdir($this->projectDir);
        }
    }

    /**
     * Prüft den Roundtrip: Speichern -> Finden.
     */
    public function testSaveAndFind(): void
    {
        $this->repo->save('test-page', 'Test Titel', '<h1>Content</h1>');
        
        $page = $this->repo->findBySlug('test-page');
        
        $this->assertNotNull($page);
        $this->assertEquals('Test Titel', $page['title']);
        $this->assertEquals('<h1>Content</h1>', $page['content']);
    }

    /**
     * Prüft, ob alle Dateien gefunden werden.
     */
    public function testFindAll(): void
    {
        $this->repo->save('p1', 'P1', 'C1');
        $this->repo->save('p2', 'P2', 'C2');

        $pages = $this->repo->findAll();
        
        $this->assertCount(2, $pages);
    }

    /**
     * Prüft das Löschen einer Datei.
     */
    public function testDelete(): void
    {
        $this->repo->save('delete-me', 'T', 'C');
        $this->assertNotNull($this->repo->findBySlug('delete-me'));
        
        $this->repo->delete('delete-me');
        $this->assertNull($this->repo->findBySlug('delete-me'));
    }
}