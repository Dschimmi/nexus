<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Persistence;

use MrWo\Nexus\Infrastructure\Persistence\FilePageRepository;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

/**
 * Testet das Datei-basierte PageRepository.
 * 
 * Validiert:
 * - Speichern und Laden von HTML-Dateien (Parsing von <title> und <body>).
 * - Auflisten aller Seiten.
 * - Löschen von Seiten.
 * - Automatische Erstellung des Verzeichnisses.
 */
class FilePageRepositoryTest extends TestCase
{
    private vfsStreamDirectory $root;
    private FilePageRepository $repo;

    /**
     * Initialisiert das virtuelle Dateisystem.
     */
    protected function setUp(): void
    {
        // Erstellt eine virtuelle Root-Struktur 'root' im Speicher
        $this->root = vfsStream::setup('root');
        
        // Repo mit vfs:// Pfad initialisieren
        // Der Konstruktor hängt '/public/pages' an, was vfsStream automatisch erstellt.
        $this->repo = new FilePageRepository(vfsStream::url('root'));
    }

    // Kein tearDown() nötig, da vfsStream im Speicher lebt und automatisch bereinigt wird!

    /**
     * Prüft den Roundtrip: Speichern -> Finden.
     */
    public function testSaveAndFind(): void
    {
        // Act
        $this->repo->save('test-page', 'Test Titel', '<h1>Content</h1>');
        
        // Assert: Datei muss im virtuellen FS existieren
        $this->assertTrue($this->root->hasChild('public/pages/test-page.html'));
        
        // Assert: Laden und Parsen
        $page = $this->repo->findBySlug('test-page');
        
        $this->assertNotNull($page);
        $this->assertEquals('test-page', $page['slug']);
        $this->assertEquals('Test Titel', $page['title']);
        $this->assertEquals('<h1>Content</h1>', $page['content']);
    }

    /**
     * Prüft, ob findAll alle Dateien findet.
     */
    public function testFindAll(): void
    {
        $this->repo->save('p1', 'P1', 'C1');
        $this->repo->save('p2', 'P2', 'C2');

        $pages = $this->repo->findAll();
        
        $this->assertCount(2, $pages);
        // Prüfen ob Slugs stimmen (Reihenfolge ist alphabetisch)
        $this->assertEquals('p1', $pages[0]['slug']);
        $this->assertEquals('p2', $pages[1]['slug']);
    }

    /**
     * Prüft das Löschen einer Datei.
     */
    public function testDelete(): void
    {
        $this->repo->save('delete-me', 'T', 'C');
        $this->assertTrue($this->root->hasChild('public/pages/delete-me.html'));
        
        $this->repo->delete('delete-me');
        
        $this->assertFalse($this->root->hasChild('public/pages/delete-me.html'));
        $this->assertNull($this->repo->findBySlug('delete-me'));
    }

    /**
     * Prüft, ob ungültige Slugs null zurückgeben.
     */
    public function testFindReturnsNullForMissingFile(): void
    {
        $this->assertNull($this->repo->findBySlug('gibt-es-nicht'));
    }
}