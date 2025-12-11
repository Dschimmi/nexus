<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use MrWo\Nexus\Kernel\Kernel;

/**
 * Testet den kompletten Request-Lifecycle des Kernels.
 * (Ticket 0000074: Test-Suite für Kernkomponenten)
 * @coversDefaultClass \MrWo\Nexus\Kernel\Kernel
 */
class KernelTest extends TestCase
{
    // Die setUp-Methode ist nicht länger notwendig, da der Kernel den Pfad nicht mehr als Argument erwartet.

    /**
     * Testet, ob der Kernel eine einfache GET-Anfrage zur Homepage verarbeiten kann 
     * und eine Symfony Response mit 200 OK zurückgibt.
     * @covers ::handleRequest
     */
    public function testKernelHandlesBasicRequest(): void
    {
        // 1. Request simulieren
        $request = Request::create('/');

        // 2. Kernel instanziieren (Umgebung 'test')
        // KORREKTUR: Der Kernel erwartet nur die Umgebung ($appEnv).
        $kernel = new Kernel('test');
        
        // 3. Request verarbeiten (Korrekter Methodenname: handleRequest)
        $response = $kernel->handleRequest($request);

        // 4. Assertions
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Nexus Enterprise Framework', $response->getContent(), 'Response sollte den Framework-Titel enthalten.');
        $this->assertStringContainsString('homepage-layout', $response->getContent(), 'Response sollte das korrekte Layout enthalten.');
    }

    /**
     * Testet, ob der Kernel korrekt mit einer nicht existierenden Route umgeht 
     * und eine 404 Not Found Response generiert.
     * @covers ::handleRequest
     */
    public function testKernelHandlesNotFoundRoute(): void
    {
        $request = Request::create('/does-not-exist-' . uniqid(), 'GET');
        
        // KORREKTUR: Der Kernel erwartet nur die Umgebung ($appEnv).
        $kernel = new Kernel('test');
        
        $response = $kernel->handleRequest($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('404 Not Found', $response->getContent(), '404-Seite sollte den korrekten Titel enthalten.');
    }
}