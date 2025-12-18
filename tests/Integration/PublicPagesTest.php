<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use MrWo\Nexus\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Testet alle statischen, öffentlichen Seiten.
 * Stellt sicher, dass Routing und Controller-Wiring funktionieren.
 */
class PublicPagesTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test');
        // Setze den Container und lade die Routenkonfiguration
        $reflection = new \ReflectionClass($this->kernel);
        $containerProperty = $reflection->getProperty('container');
        $containerProperty->setAccessible(true);
        $container = $containerProperty->getValue($this->kernel);

        // Lade die Routenkonfiguration manuell
        $routes = require __DIR__ . '/../../config/routes.php';

        // Erstelle den UrlMatcher und setze ihn in den Container
        $context = new \Symfony\Component\Routing\RequestContext();
        $matcher = new \Symfony\Component\Routing\Matcher\UrlMatcher($routes, $context);
        $container->set('router', $matcher);
    }
    

    /**
     * @dataProvider publicUrlsProvider
     */
    public function testPublicPageIsaccessible(string $url, string $expectedContent): void
    {
        $request = Request::create($url, 'GET');
        $response = $this->kernel->handleRequest($request);

        $this->assertEquals(200, $response->getStatusCode(), "URL $url failed");
        $this->assertStringContainsString($expectedContent, $response->getContent());
    }

    public static function publicUrlsProvider(): array
    {
        return [
            ['/impressum', 'Impressum'],
            ['/datenschutz', 'Datenschutz'],
            ['/kontakt', 'Kontakt'],
            ['/', 'Exelor'], // Homepage
        ];
    }
}