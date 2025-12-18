<?php

namespace MrWo\Nexus\Tests\Kernel;

use MrWo\Nexus\Kernel\Kernel;
use MrWo\Nexus\Controller\HomepageController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use MrWo\Nexus\Infrastructure\Config\ConfigService;
use MrWo\Nexus\Application\Auth\AuthenticationService;
use Twig\Environment;

/**
 * @covers \MrWo\Nexus\Kernel\Kernel
 * @uses   \Symfony\Component\HttpFoundation\Request
 * @uses   \Symfony\Component\HttpFoundation\Response
 */
class KernelTest extends TestCase
{
    private Kernel $kernel;
    private ContainerBuilder $container;
    private string $appEnv = 'development';

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->kernel = new Kernel($this->appEnv);

    // Injiziere den Container via Reflection
    $reflection = new \ReflectionClass($this->kernel);
    $property = $reflection->getProperty('container');
    $property->setAccessible(true);
    $property->setValue($this->kernel, $this->container);

    // Mock für resolveRoute(), der eine Route zurückgibt
    $reflection = new \ReflectionClass($this->kernel);
    $method = $reflection->getMethod('resolveRoute');
    $method->setAccessible(true);

    // Mock für SessionService (ohne willReturn für void-Methoden)
    $sessionService = $this->createMock(\MrWo\Nexus\Infrastructure\Session\SessionService::class);
    $sessionService->expects($this->any())
        ->method('start');
    $sessionService->expects($this->any())
        ->method('save');
    $this->container->set('session_service', $sessionService);

    // Mock für ConfigService
    $configService = $this->createMock(ConfigService::class);
    $configService->method('get')
        ->with('security.content_security_policy', Kernel::DEFAULT_CSP)
        ->willReturn(Kernel::DEFAULT_CSP);
    $this->container->set(ConfigService::class, $configService);

    // Mock für AuthenticationService
    $authService = $this->createMock(AuthenticationService::class);
    $authService->method('getUser')->willReturn(null);
    $this->container->set(AuthenticationService::class, $authService);

    // Mock für Twig
    $twig = $this->createMock(Environment::class);
    $this->container->set(Environment::class, $twig);
    }

    /**
     * Testet, ob generateCspNonce() einen gültigen Base64-String zurückgibt.
     * @covers \MrWo\Nexus\Kernel\Kernel::generateCspNonce
     */
    public function testGenerateCspNonceReturnsBase64String(): void
    {
        $reflection = new \ReflectionClass($this->kernel);
        $method = $reflection->getMethod('generateCspNonce');
        $method->setAccessible(true);

        $nonce = $method->invoke($this->kernel);

        // Base64-String mit 22 Zeichen (16 Bytes Base64-kodiert).
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\+\/]+={0,2}$/', $nonce);
        $this->assertEquals(24, strlen($nonce));
    }

    /**
     * Testet, ob setSecurityHeaders() die erwarteten Sicherheitsheader setzt.
     * @covers \MrWo\Nexus\Kernel\Kernel::setSecurityHeaders
     */
    public function testSetSecurityHeadersAddsExpectedHeaders(): void
    {
        $response = new Response();

        $reflection = new \ReflectionClass($this->kernel);
        $method = $reflection->getMethod('setSecurityHeaders');
        $method->setAccessible(true);
        $method->invoke($this->kernel, $response);

        // Prüfe, ob alle Sicherheitsheader gesetzt wurden.
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
        $this->assertTrue($response->headers->has('Referrer-Policy'));
        // HSTS wird in 'development' nicht gesetzt.
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }
}
