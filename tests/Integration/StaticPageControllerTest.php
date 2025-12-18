<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use MrWo\Nexus\Controller\StaticPageController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Testet die statischen Seiten.
 */
class StaticPageControllerTest extends TestCase
{
    private $twigMock;
    private $controller;

    protected function setUp(): void
    {
        $this->twigMock = $this->createMock(Environment::class);
        $this->controller = new StaticPageController($this->twigMock);
    }

    /**
     * Testet die Impressum-Seite.
     */
    public function testImprintRendersTemplate(): void
    {
        $this->twigMock->expects($this->once())
            ->method('render')
            ->with('imprint.html.twig')
            ->willReturn('<html>Imprint</html>');

        $response = $this->controller->imprint();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testet die Datenschutz-Seite.
     */
    public function testPrivacyRendersTemplate(): void
    {
        $this->twigMock->expects($this->once())
            ->method('render')
            ->with('privacy.html.twig')
            ->willReturn('<html>Privacy</html>');

        $response = $this->controller->privacy();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Testet die Kontakt-Seite.
     */
    public function testContactRendersTemplate(): void
    {
        $this->twigMock->expects($this->once())
            ->method('render')
            ->with('contact.html.twig')
            ->willReturn('<html>Contact</html>');

        $response = $this->controller->contact();

        $this->assertEquals(200, $response->getStatusCode());
    }
}