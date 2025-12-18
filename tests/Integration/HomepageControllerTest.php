<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use MrWo\Nexus\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Integrationstest für die Startseite.
 * Prüft den kompletten Request-Lifecycle.
 */
class HomepageControllerTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test');
    }

    public function testHomepageIsAccessible(): void
    {
        $request = Request::create('/', 'GET');
        $response = $this->kernel->handleRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        // Prüft, ob Twig gerendert wurde (Inhalt aus translations/de.php)
        // "Exelor" ist der Brand-Name, der immer da sein sollte.
        $this->assertStringContainsString('Exelor', $response->getContent());
    }
}