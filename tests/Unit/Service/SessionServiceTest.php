<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\SessionBag;
use MrWo\Nexus\Service\ConfigService;
use PHPUnit\Framework\TestCase;

/**
 * Testet die Logik des SessionService.
 * Mockt den ConfigService, um Abhängigkeiten zu isolieren.
 */
class SessionServiceTest extends TestCase
{
    private SessionService $sessionService;
    private $configMock;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        
        // ConfigService Mock erstellen
        $this->configMock = $this->createMock(ConfigService::class);
        
        // Standardwerte für den Mock definieren (damit der Konstruktor nicht crasht)
        $this->configMock->method('get')->willReturnMap([
            ['session.lifetime', null, 1800],
            ['session.absolute_lifetime', null, 43200],
            ['app.secret', null, 'test_secret'],
            ['app.name', null, 'TestApp'],
        ]);
        
        $this->sessionService = new SessionService($this->configMock);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetBagCreatesNewBag(): void
    {
        $bag = $this->sessionService->getBag('test_bag');
        $this->assertInstanceOf(SessionBag::class, $bag);
        $this->assertEmpty($bag->all());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDataPersistenceBetweenBags(): void
    {
        $bag = $this->sessionService->getBag('attributes');
        $bag->set('theme', 'dark');

        $this->sessionService->start();
        $this->sessionService->save();

        $this->assertArrayHasKey('attributes', $_SESSION);
        $this->assertEquals('dark', $_SESSION['attributes']['theme']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddAndGetFlashMessages(): void
    {
        $this->sessionService->addFlash('success', 'Alles super');
        $this->sessionService->addFlash('error', 'Oje');

        $flashes = $this->sessionService->getFlashes();

        $this->assertCount(2, $flashes);
        $this->assertEquals('Alles super', $flashes['success'][0]);
        $this->assertEquals('Oje', $flashes['error'][0]);

        $flashesEmpty = $this->sessionService->getFlashes();
        $this->assertEmpty($flashesEmpty);
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidateClearsEverything(): void
    {
        // 1. Arrange
        $this->sessionService->getBag('security')->set('user_id', 123);
        $this->sessionService->save();
        
        // 2. Act
        $this->sessionService->invalidate();

        // 3. Assert (Sofort prüfen!)
        $this->assertEmpty($_SESSION, 'Session should be empty immediately after invalidate');

        // 4. Force New Session ID (Simulate Browser behavior)
        session_id(uniqid());

        // 5. Verify Bag Reset with fresh service (und neuem Mock)
        $this->sessionService = new SessionService($this->configMock);
        $newBag = $this->sessionService->getBag('security');
        
        $this->assertNull($newBag->get('user_id'), 'Bag should be empty/re-initialized');
    }
}