<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Infrastructure\Session\SessionService;
use MrWo\Nexus\Infrastructure\Session\SessionBag;
use MrWo\Nexus\Infrastructure\Config\ConfigService;
use MrWo\Nexus\Infrastructure\Security\SecurityLogger;
use PHPUnit\Framework\TestCase;
use SessionHandlerInterface;

/**
 * Testet die Session-Verwaltung (Nexus Session 2.0).
 * 
 * Fokus:
 * - Isolation durch Bags
 * - Persistenz (Save)
 * - Flash-Messages
 * - Invalidation (Logout)
 * - Mocking der Infrastruktur (Config, Logger, Handler)
 */
class SessionServiceTest extends TestCase
{
    private SessionService $sessionService;
    private $configMock;
    private $handlerMock;
    private $loggerMock;

    /**
     * Initialisiert die Testumgebung vor jedem Test.
     * Setzt $_SESSION zurück und mockt alle Abhängigkeiten.
     */
    protected function setUp(): void
    {
        // 1. Session-Environment säubern
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        
        // 2. Mocking der Abhängigkeiten
        $this->configMock = $this->createMock(ConfigService::class);
        $this->handlerMock = $this->createMock(SessionHandlerInterface::class);
        $this->loggerMock = $this->createMock(SecurityLogger::class);

        // 3. Konfiguration simulieren (für Constructor)
        // Wir definieren Standardwerte für Lifetime und Secret.
        $this->configMock->method('get')
            ->willReturnMap([
                ['session.lifetime', null, 1800],
                ['session.absolute_lifetime', null, 43200],
                ['app.secret', null, 'test-secret-key-123'],
                ['app.name', null, 'NexusTestApp']
            ]);

        // 4. Service instanziieren
        $this->sessionService = new SessionService(
            $this->configMock,
            $this->handlerMock,
            $this->loggerMock
        );
    }

    /**
     * Prüft, ob getBag() einen korrekten SessionBag zurückgibt
     * und ob dieser initial leer ist.
     * 
     * @runInSeparateProcess Um Seiteneffekte mit session_start() zu vermeiden.
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
     * Prüft, ob Daten über Bags hinweg in die globale Session geschrieben werden,
     * wenn save() aufgerufen wird.
     * 
     * @runInSeparateProcess
     */
    public function testDataPersistenceBetweenBags(): void
    {
        // Arrange
        $bag = $this->sessionService->getBag('attributes');
        $bag->set('theme', 'dark');

        // Act
        $this->sessionService->start(); // Startet intern session_start()
        $this->sessionService->save();  // Schreibt Bags in $_SESSION

        // Assert
        $this->assertArrayHasKey('attributes', $_SESSION, 'Bag key should exist in $_SESSION');
        $this->assertEquals('dark', $_SESSION['attributes']['theme']);
    }

    /**
     * Prüft die Flash-Message Logik (Hinzufügen, Abrufen, Auto-Löschen).
     * 
     * @runInSeparateProcess
     */
    public function testAddAndGetFlashMessages(): void
    {
        // Arrange
        $this->sessionService->addFlash('success', 'Alles super');

        // Act 1: Abrufen
        $flashes = $this->sessionService->getFlashes();

        // Assert 1: Nachricht muss da sein
        $this->assertCount(1, $flashes);
        $this->assertEquals('Alles super', $flashes['success'][0]);

        // Act 2: Erneutes Abrufen (sollte leer sein)
        $flashesEmpty = $this->sessionService->getFlashes();
        $this->assertEmpty($flashesEmpty, 'Flash messages should be cleared after reading');
    }

    /**
     * Prüft, ob invalidate() alle Daten löscht und den Logger benachrichtigt.
     * 
     * @runInSeparateProcess
     */
    public function testInvalidateClearsEverything(): void
    {
        // Arrange
        $this->sessionService->getBag('security')->set('user_id', 123);
        $this->sessionService->save();
        
        // Expectation: Der Logger muss über die Invalidation informiert werden
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('log')
            ->with($this->stringContains('session_invalidation')); // Prüft auf Event-Typ

        // Act
        $this->sessionService->invalidate();
        
        // Workaround für PHPUnit: Wir simulieren, dass das Cookie gelöscht wurde,
        // indem wir eine neue Session-ID erzwingen, damit PHP beim nächsten Start
        // nicht die alten Daten lädt.
        session_id(uniqid());

        // Verify: Neuer Service (neuer Request) darf keine Daten mehr finden
        $newService = new SessionService($this->configMock, $this->handlerMock, $this->loggerMock);
        $newBag = $newService->getBag('security');
        
        $this->assertNull($newBag->get('user_id'), 'Bag should be empty after invalidate');
    }
}