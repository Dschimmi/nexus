<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\SessionBag;
use PHPUnit\Framework\TestCase;

/**
 * Testet die Logik des SessionService.
 * Hinweis: Da native PHP-Sessions im CLI-Modus (PHPUnit) schwer zu testen sind,
 * konzentrieren wir uns auf die Bag-Logik und Flash-Messages.
 * Die echten session_* Funktionen werden hier teilweise ausgeführt, 
 * wir nutzen @runInSeparateProcess um Seiteneffekte zu minimieren.
 */
class SessionServiceTest extends TestCase
{
    private SessionService $sessionService;

    protected function setUp(): void
    {
        // Wir setzen $_SESSION manuell zurück, um einen sauberen State zu haben
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        
        $this->sessionService = new SessionService();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetBagCreatesNewBag(): void
    {
        // Act
        $bag = $this->sessionService->getBag('test_bag');

        // Assert
        $this->assertInstanceOf(SessionBag::class, $bag);
        $this->assertEmpty($bag->all());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDataPersistenceBetweenBags(): void
    {
        // Arrange
        $bag = $this->sessionService->getBag('attributes');
        $bag->set('theme', 'dark');

        // Act - Simulate Save (write to $_SESSION)
        $this->sessionService->start(); // Muss gestartet sein
        $this->sessionService->save();

        // Assert - Check global $_SESSION
        $this->assertArrayHasKey('attributes', $_SESSION);
        $this->assertEquals('dark', $_SESSION['attributes']['theme']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddAndGetFlashMessages(): void
    {
        // Arrange
        $this->sessionService->addFlash('success', 'Alles super');
        $this->sessionService->addFlash('error', 'Oje');

        // Act
        $flashes = $this->sessionService->getFlashes();

        // Assert
        $this->assertCount(2, $flashes);
        $this->assertEquals('Alles super', $flashes['success'][0]);
        $this->assertEquals('Oje', $flashes['error'][0]);

        // Act 2 - Check Auto-Expire
        $flashesEmpty = $this->sessionService->getFlashes();
        $this->assertEmpty($flashesEmpty, 'Flash messages should be cleared after reading');
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidateClearsEverything(): void
    {
        // Arrange
        $this->sessionService->getBag('security')->set('user_id', 123);
        $this->sessionService->save();
        
        $this->assertNotEmpty($_SESSION);

        // Act
        $this->sessionService->invalidate();

        // Assert
        $this->assertEmpty($_SESSION, 'Session should be empty after invalidate');
        // Prüfen ob interner Cache auch leer ist
        $bag = $this->sessionService->getBag('security');
        $this->assertNull($bag->get('user_id'), 'Bag should be empty/re-initialized');
    }
}