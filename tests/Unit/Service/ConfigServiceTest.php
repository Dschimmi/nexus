<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Infrastructure\Config\ConfigService;
use MrWo\Nexus\Repository\ConfigRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Testet die Konfigurations-Logik.
 * Validiert das Laden von Defaults (.env) und Persistenz (Repository).
 */
class ConfigServiceTest extends TestCase
{
    private $repositoryMock;
    private $configService;

    protected function setUp(): void
    {
        // 1. Repository Mocken
        $this->repositoryMock = $this->createMock(ConfigRepositoryInterface::class);

        // 2. Environment simulieren (für Defaults)
        $_ENV['APP_NAME'] = 'NexusTest';
        $_ENV['SESSION_LIFETIME'] = '100';

        // 3. Repository Verhalten definieren (Load returns empty array initially)
        $this->repositoryMock->method('load')->willReturn([]);

        $this->configService = new ConfigService($this->repositoryMock);
    }

    /**
     * Prüft, ob Werte aus $_ENV als Fallback geladen werden.
     */
    public function testLoadDefaultsFromEnv(): void
    {
        $this->assertEquals('NexusTest', $this->configService->get('app.name'));
        $this->assertEquals(100, $this->configService->get('session.lifetime'));
    }

    /**
     * Prüft, ob gespeicherte Werte Vorrang vor Defaults haben.
     */
    public function testLoadExistingConfigOverridesDefaults(): void
    {
        // Arrange: Repository liefert überschriebenen Wert
        $this->repositoryMock = $this->createMock(ConfigRepositoryInterface::class);
        $this->repositoryMock->method('load')->willReturn(['app.name' => 'CustomName']);
        
        $service = new ConfigService($this->repositoryMock);

        // Assert
        $this->assertEquals('CustomName', $service->get('app.name'));
    }

    /**
     * Prüft, ob set() den Wert im Repository speichert.
     */
    public function testSetSavesConfigToFile(): void
    {
        // Expect: Save wird aufgerufen mit dem neuen Array
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($settings) {
                return $settings['new_key'] === 'new_value';
            }));

        // Act
        $this->configService->set('new_key', 'new_value');
    }
}