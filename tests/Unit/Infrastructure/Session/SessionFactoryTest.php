<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Session;

use MrWo\Nexus\Infrastructure\Config\ConfigService;
use MrWo\Nexus\Infrastructure\Session\SessionFactory;
use PHPUnit\Framework\TestCase;
use SessionHandler;
use RuntimeException;

/**
 * Testet die SessionFactory.
 */
class SessionFactoryTest extends TestCase
{
    private $configMock;
    private $factory;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(ConfigService::class);
        $this->factory = new SessionFactory($this->configMock);
    }

    /**
     * Prüft, ob der Standard-PHP-Handler erstellt wird.
     */
    public function testCreateHandlerReturnsNativeHandler(): void
    {
        $this->configMock->method('get')->willReturn('native');
        $handler = $this->factory->createHandler();
        $this->assertInstanceOf(SessionHandler::class, $handler);
    }

    /**
     * Prüft, ob 'files' alias auch funktioniert.
     */
    public function testCreateHandlerReturnsNativeHandlerForFiles(): void
    {
        $this->configMock->method('get')->willReturn('files');
        $handler = $this->factory->createHandler();
        $this->assertInstanceOf(SessionHandler::class, $handler);
    }

    /**
     * Prüft, ob Redis eine Exception wirft (noch nicht implementiert).
     */
    public function testCreateHandlerThrowsForRedis(): void
    {
        $this->configMock->method('get')->willReturn('redis');
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not yet implemented');
        
        $this->factory->createHandler();
    }

    /**
     * Prüft, ob unbekannte Typen abgewiesen werden.
     */
    public function testCreateHandlerThrowsForUnknownType(): void
    {
        $this->configMock->method('get')->willReturn('unknown_type');
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown session handler type');
        
        $this->factory->createHandler();
    }
}