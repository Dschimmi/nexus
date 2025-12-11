<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use MrWo\Nexus\Controller\HomepageController;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;

/**
 * Testet den HomepageController, der als Invokable Controller (über __invoke) fungiert.
 * (Ticket 0000074: Keine Controller-Tests)
 * @coversDefaultClass \MrWo\Nexus\Controller\HomepageController
 */
class HomepageControllerTest extends TestCase
{
    /**
     * Testet die Standard-Aktion des Controllers, die über __invoke aufgerufen wird.
     * @covers ::__invoke
     */
    public function testInvokeActionReturnsSuccessfulResponse(): void
    {
        // Mocking des Twig Environments
        $twigMock = $this->createMock(Environment::class);
        
        // Simuliert, dass Twig das Template rendert und einen Dummy-Inhalt zurückgibt
        $twigMock->expects($this->once())
                 ->method('render')
                 ->with('homepage.html.twig', $this->isType('array'))
                 ->willReturn('<html>Homepage Content</html>');

        // Request simulieren (wird vom Controller nicht genutzt, ist aber Standard-Setup)
        $request = Request::create('/');
        
        // Controller instanziieren
        $controller = new HomepageController($twigMock); // Request wird nicht über DI injiziert, nur Twig
        
        // Methode aufrufen
        $response = $controller(); // Ruft __invoke() auf

        // Assertions
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Homepage Content', $response->getContent());
    }
}