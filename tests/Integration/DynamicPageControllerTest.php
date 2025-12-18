<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use MrWo\Nexus\Controller\DynamicPageController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Twig\Environment;

/**
 * Testet den DynamicPageController.
 * Validiert das Laden von HTML-Fragmenten und die Fehlerbehandlung.
 */
class DynamicPageControllerTest extends TestCase
{
    private $twigMock;
    private $projectDir;
    private $controller;

    /**
     * Erstellt eine temporäre Dateistruktur für die Tests.
     */
    protected function setUp(): void
    {
        $this->twigMock = $this->createMock(Environment::class);
        
        $this->projectDir = sys_get_temp_dir() . '/nexus_test_pages_' . uniqid();
        mkdir($this->projectDir . '/public/pages', 0777, true);

        $this->controller = new DynamicPageController($this->twigMock, $this->projectDir);
    }

    /**
     * Räumt die temporären Dateien auf.
     */
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
     * Prüft, ob eine existierende Seite korrekt geladen und gerendert wird.
     * Erwartet: Titel-Extraktion aus Kommentar und Übergabe an Twig.
     */
    public function testShowRendersExistingPage(): void
    {
        $content = "<!-- TITLE: Test Page --><h1>Hello</h1>";
        file_put_contents($this->projectDir . '/public/pages/test.html', $content);

        $this->twigMock->expects($this->once())
            ->method('render')
            ->with('dynamic_page.html.twig', [
                'page_title' => 'Test Page',
                'page_content' => $content
            ])
            ->willReturn('<html>Rendered</html>');

        $response = $this->controller->show('test');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Prüft, ob der Slug bereinigt wird, bevor die Datei gesucht wird.
     * 'Bad! Slug' -> 'badslug'.
     */
    public function testShowSanitizesSlug(): void
    {
        // Wir legen die Datei unter dem bereinigten Namen an
        file_put_contents($this->projectDir . '/public/pages/bad-slug.html', 'Content');

        // Der Aufruf mit dem "schmutzigen" Slug muss erfolgreich sein
        $this->twigMock->expects($this->once())->method('render');

        $this->controller->show('Bad! Slug');
    }

    /**
     * Prüft, ob eine ResourceNotFoundException geworfen wird, wenn die Datei fehlt.
     */
    public function testShowThrowsExceptionIfPageNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->controller->show('non-existent');
    }
}