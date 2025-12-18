<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Controller\Api;

use MrWo\Nexus\Controller\Api\ApiController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Testet die Basis-Methoden des ApiControllers.
 * Wir nutzen eine anonyme Klasse, um die abstrakte Klasse zu testen.
 */
class ApiControllerTest extends TestCase
{
    private $controller;

    protected function setUp(): void
    {
        // Anonyme Klasse, die ApiController erweitert und Methoden öffentlich macht
        $this->controller = new class extends ApiController {
            public function publicJson(mixed $data, int $status = 200, array $headers = []): JsonResponse
            {
                return $this->json($data, $status, $headers);
            }

            public function publicError(string $message, int $status = 400): JsonResponse
            {
                return $this->error($message, $status);
            }
        };
    }

    public function testJsonReturnsCorrectResponse(): void
    {
        $data = ['foo' => 'bar'];
        $response = $this->controller->publicJson($data, 201, ['X-Test' => '1']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('1', $response->headers->get('X-Test'));
        $this->assertJsonStringEqualsJsonString(json_encode($data), $response->getContent());
    }

    public function testErrorReturnsStandardFormat(): void
    {
        $response = $this->controller->publicError('Something went wrong', 404);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        
        $expected = ['error' => 'Something went wrong'];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $response->getContent());
    }
}