<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\SessionBag;
use MrWo\Nexus\Service\ConfigService;
use MrWo\Nexus\Service\SecurityLogger;
use PHPUnit\Framework\TestCase;
use SessionHandlerInterface;
use RuntimeException;

/**
 * Testet die Logik des SessionService.
 * Fokus: Bag-Verwaltung, Flash Messages, CSRF und interne Session-Funktionen.
 * @coversDefaultClass \MrWo\Nexus\Service\SessionService
 * @runInSeparateProcess
 */
class SessionServiceTest extends TestCase
{
    private SessionService $sessionService;
    private $configMock;
    private $handlerMock;
    private $loggerMock;

    protected function setUp(): void
    {
        // Session-Status aufräumen
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $_SESSION = [];
        
        // 1. ConfigService Mock erstellen
        $this->configMock = $this->createMock(ConfigService::class);
        $this->configMock->method('get')->willReturnMap([
            ['session.lifetime', null, 1800],
            ['session.absolute_lifetime', null, 43200],
            ['app.secret', null, 'test_secret'],
            ['app.name', null, 'TestApp'],
        ]);
        
        // 2. SessionHandlerInterface Mock erstellen (Muss existieren, auch wenn es nichts tut)
        $this->handlerMock = $this->createMock(SessionHandlerInterface::class);
        $this->handlerMock->method('read')->willReturn('');
        $this->handlerMock->method('write')->willReturn(true);
        $this->handlerMock->method('close')->willReturn(true);
        $this->handlerMock->method('destroy')->willReturn(true);
        $this->handlerMock->method('gc')->willReturn(1);
        
        // 3. SecurityLogger Mock erstellen (Für migrate, validate)
        $this->loggerMock = $this->createMock(SecurityLogger::class);
        // SecurityLogger::anonymizeIp muss gemockt werden, da validateSession dies nutzt.
        $this->loggerMock->method('anonymizeIp')->willReturn('127.0.0.1'); 
        $this->loggerMock->method('parseUserAgent')->willReturn('MacOS|Chrome/123'); 
        
        // 4. SessionService mit 3 Parametern instanziieren (KORREKTUR: Alle 3 Argumente)
        $this->sessionService = new SessionService(
            $this->configMock,
            $this->handlerMock,
            $this->loggerMock
        );
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $_SESSION = [];
    }

    /**
     * Prüft, ob getBag die Session startet, einen Bag erstellt und das Bag-Objekt zurückgibt.
     * @covers ::getBag
     * @covers ::start
     */
    public function testGetBagCreatesNewBagAndStartsSession(): void
    {
        $bag = $this->sessionService->getBag('attributes');
        $this->assertInstanceOf(SessionBag::class, $bag);
    }

    /**
     * Prüft, ob Daten über save() in $_SESSION geschrieben werden.
     * @covers ::save
     */
    public function testDataPersistenceViaSave(): void
    {
        // 1. Arrange & Act: Daten setzen und speichern
        $bag = $this->sessionService->getBag('attributes');
        $bag->set('theme', 'dark');

        $this->sessionService->save();
        
        // 2. Assert: Überprüfen, ob die Daten in $_SESSION persistiert wurden.
        // Die Session ist nach save() geschlossen, aber wir können die Daten in $_SESSION
        // prüfen, da sie direkt vor session_write_close() geschrieben werden.
        $this->assertArrayHasKey('attributes', $_SESSION);
        $this->assertEquals('dark', $_SESSION['attributes']['theme']);
    }

    /**
     * Testet die CSRF-Logik, die intern den 'security' Bag nutzt.
     * @covers ::generateCsrfToken
     * @covers ::isCsrfTokenValid
     */
    public function testCsrfTokenGenerationAndValidation(): void
    {
        $tokenId = 'form_login';
        
        /// 1. Token generieren
        $token = $this->sessionService->generateCsrfToken($tokenId);
        
        // KORREKTUR: assertIsString wurde entfernt, da die statische Analyse 
        // den Rückgabetyp als String erkennt und die Assertion redundant ist.
        $this->assertGreaterThan(20, strlen($token)); 
        
        // 2. Gültigkeit prüfen
        $this->assertTrue(
            $this->sessionService->isCsrfTokenValid($tokenId, $token), 
            'Der generierte Token sollte gültig sein.'
        );

        // 3. Ungültigkeit prüfen
        $this->assertFalse(
            $this->sessionService->isCsrfTokenValid($tokenId, 'wrong-token'), 
            'Falscher Token sollte ungültig sein.'
        );

        // 4. Nicht existierende Token-ID prüfen
        $this->assertFalse(
            $this->sessionService->isCsrfTokenValid('nonexistent', $token),
            'Nicht existierende ID sollte fehlschlagen.'
        );
    }
    
    /**
     * Testet die addFlash/getFlashes Shortcut-Methoden.
     * @covers ::addFlash
     * @covers ::getFlashes
     */
    public function testAddAndGetFlashMessages(): void
    {
        // 1. Hinzufügen (nutzt 'flash' Bag)
        $this->sessionService->addFlash('success', 'Alles super');
        $this->sessionService->addFlash('error', 'Oje');
        
        // 2. Abrufen und Leeren
        $flashes = $this->sessionService->getFlashes();

        $this->assertArrayHasKey('success', $flashes);
        $this->assertArrayHasKey('error', $flashes);
        $this->assertEquals(['Alles super'], $flashes['success']);
        
        // 3. Zweites Abrufen muss leer sein
        $flashesEmpty = $this->sessionService->getFlashes();
        $this->assertEmpty($flashesEmpty, 'Flashes sollten nach dem ersten Abruf geleert sein.');
    }

    /**
     * Testet die migrate-Methode.
     * @covers ::migrate
     */
    public function testMigrateRegeneratesId(): void
    {
        // Arrange: Session starten
        $this->sessionService->start();
        $oldId = session_id();
        
        // Act
        $this->sessionService->migrate(false);

        // Assert: ID muss sich geändert haben
        $newId = session_id();
        $this->assertNotEquals($oldId, $newId, 'Session ID sollte regeneriert werden.');
        
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('session_migration', $this->isType('array'));
    }
    
    /**
     * Testet die invalidate-Methode.
     * @covers ::invalidate
     */
    public function testInvalidateDestroysSessionAndClearsBags(): void
    {
        // Arrange: Daten setzen, um den Bag-Cache zu füllen
        $this->sessionService->getBag('attributes')->set('key', 'value');
        $this->sessionService->save(); // Stellt sicher, dass die Daten auch in $_SESSION sind
        
        // Act
        $this->sessionService->invalidate();

        // Assert: $_SESSION sollte leer sein
        $this->assertEmpty($_SESSION, '$_SESSION sollte nach Invalidate leer sein.');
        
        // Assert: Logger wurde aufgerufen
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('session_invalidation');

        // Re-Test des Bags (sollte leer sein)
        $newBag = $this->sessionService->getBag('attributes');
        $this->assertNull($newBag->get('key'), 'Nach Invalidate sollte der Bag-Inhalt verloren sein.');
    }
}