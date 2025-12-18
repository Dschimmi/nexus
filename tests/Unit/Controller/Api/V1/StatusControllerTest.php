<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Controller\Api\V1;

use MrWo\Nexus\Controller\Api\V1\StatusController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Testet den Status-Endpunkt der API.
 */
class StatusControllerTest extends TestCase
{
    /**
     * Prüft, ob der Ping-Endpunkt die erwartete JSON-Struktur zurückgibt.
     * Erwartet: Status 200, Schlüssel 'status', 'api_version' und 'timestamp'.
     */
    public function testPingReturnsOperationalStatus(): void
    {
        $controller = new StatusController();
        $response = $controller->ping();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('status', $content);
        $this->assertEquals('operational', $content['status']);
        
        $this->assertArrayHasKey('api_version', $content);
        $this->assertEquals('v1', $content['api_version']);
        
        $this->assertArrayHasKey('timestamp', $content);
    }
}