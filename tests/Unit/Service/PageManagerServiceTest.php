<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\PageManagerService;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class PageManagerServiceTest extends TestCase
{
    /**
     * @var vfsStreamDirectory Das virtuelle Root-Verzeichnis
     */
    private $vfsRoot;

    /**
     * @var string Der simulierte Pfad zum Projekt-Ordner
     */
    private string $projectDir;

    protected function setUp(): void
    {
        // Wir erstellen eine virtuelle Ordnerstruktur im Speicher:
        // root/
        //   public/
        //     (pages wird vom Service erstellt)
        $this->vfsRoot = vfsStream::setup('root', 0777, [
            'public' => []
        ]);

        // vfsStream::url('root') liefert z.B. "vfs://root"
        $this->projectDir = vfsStream::url('root');
    }

    public function testConstructorCreatesPagesDirectoryIfNotExists()
    {
        // Initial: public/pages existiert noch nicht im VFS
        $this->assertFalse($this->vfsRoot->hasChild('public/pages'));

        new PageManagerService($this->projectDir);

        // Nach Instanziierung muss der Ordner da sein
        $this->assertTrue($this->vfsRoot->hasChild('public/pages'));
    }

    public function testCreatePageWritesHtmlFileAndSitemap()
    {
        $service = new PageManagerService($this->projectDir);
        $slug = 'meine-test-seite';
        $title = 'Der Titel';
        $content = '<p>Hallo Welt</p>';

        // Action
        $service->createPage($slug, $title, $content);

        // 1. Prüfung: Existiert die HTML-Datei?
        $expectedFilePath = $this->projectDir . '/public/pages/meine-test-seite.html';
        $this->assertFileExists($expectedFilePath);

        // 2. Prüfung: Inhalt der HTML-Datei (via nativem file_get_contents lesen)
        // Das behebt den VSCode-Fehler, da wir nicht mehr auf interne VFS-Objekte zugreifen müssen.
        $fileContent = file_get_contents($expectedFilePath);
        $this->assertStringContainsString('<!-- TITLE: Der Titel -->', $fileContent);
        $this->assertStringContainsString('<p>Hallo Welt</p>', $fileContent);

        // 3. Prüfung: Wurde die Sitemap generiert?
        $sitemapPath = $this->projectDir . '/public/sitemap.xml';
        $this->assertFileExists($sitemapPath);
        
        // Prüfung des Inhalts
        $sitemapContent = file_get_contents($sitemapPath);
        $this->assertStringContainsString('meine-test-seite', $sitemapContent);
    }

    public function testCreatePageSanitizesSlug()
    {
        $service = new PageManagerService($this->projectDir);
        
        // Wir übergeben einen "schmutzigen" Slug mit Großbuchstaben und Sonderzeichen
        $dirtySlug = 'UGLY_Slug!#123'; 
        
        $service->createPage($dirtySlug, 'Test', 'Content');

        // Erwartung: Nur Kleinbuchstaben, Zahlen, Bindestrich erlaubt -> ugly-slug123
        // Da unsere Logik Sonderzeichen entfernt und lowercase macht: 'uglyslug123' (oder wie regex definiert ist)
        // Regex war: preg_replace('/[^a-z0-9-]/', '', strtolower($slug)); -> ! und _ und # fliegen raus.
        
        $expectedCleanSlug = 'uglyslug123'; 
        
        $this->assertTrue($this->vfsRoot->hasChild("public/pages/{$expectedCleanSlug}.html"));
    }

    public function testCreatePageThrowsExceptionOnEmptySlug()
    {
        $service = new PageManagerService($this->projectDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ungültiger Slug');

        // Ein Slug, der nach Bereinigung leer ist (z.B. nur Sonderzeichen)
        $service->createPage('!!!', 'Titel', 'Content');
    }

    public function testGetPagesReturnsCorrectList()
    {
        // 1. Verzeichnisstruktur sicherstellen
        // Wir erstellen den Ordner manuell im VFS, damit wir Dateien hineinlegen können.
        if (!is_dir($this->projectDir . '/public/pages')) {
            mkdir($this->projectDir . '/public/pages', 0777, true);
        }

        // 2. Test-Dateien anlegen (mit nativen Funktionen, da VFS gemountet ist)
        // Datei 1: Valide HTML-Datei mit Titel
        file_put_contents(
            $this->projectDir . '/public/pages/valid-page.html', 
            "<!-- TITLE: Valide Seite -->\n<p>Content</p>"
        );
            
        // Datei 2: HTML-Datei ohne Titel (Testet Fallback auf Slug)
        file_put_contents(
            $this->projectDir . '/public/pages/no-title.html', 
            "<p>Just Content</p>"
        );

        // Datei 3: Keine HTML-Datei (Muss ignoriert werden)
        file_put_contents(
            $this->projectDir . '/public/pages/garbage.txt', 
            "Ignore me"
        );

        // 3. Service instanziieren und abfragen
        $service = new PageManagerService($this->projectDir);
        $pages = $service->getPages();

        // 4. Assertions
        
        // Erwartung: 2 HTML Dateien gefunden, txt ignoriert
        $this->assertCount(2, $pages, 'Es sollten genau 2 HTML-Dateien gefunden werden.');

        // Sortierung ist nicht garantiert (Dateisystem), daher suchen wir unsere Einträge
        
        // Test Valid Page
        // Wir filtern das Array nach dem Slug 'valid-page'
        $validPagesFound = array_values(array_filter($pages, fn($p) => $p['slug'] === 'valid-page'));
        $this->assertNotEmpty($validPagesFound, 'valid-page wurde nicht gefunden.');
        $this->assertEquals('Valide Seite', $validPagesFound[0]['title']);

        // Test Fallback Title (ucfirst slug)
        $noTitlePagesFound = array_values(array_filter($pages, fn($p) => $p['slug'] === 'no-title'));
        $this->assertNotEmpty($noTitlePagesFound, 'no-title wurde nicht gefunden.');
        $this->assertEquals('No-title', $noTitlePagesFound[0]['title']); 
    }
}