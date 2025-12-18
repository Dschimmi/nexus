<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Infrastructure\Config\ConfigService;
use MrWo\Nexus\Infrastructure\Security\RateLimiter;
use MrWo\Nexus\Infrastructure\Security\SecurityLogger;
use MrWo\Nexus\Repository\RateLimitInterface;
use PHPUnit\Framework\TestCase;

/**
 * Testet die Rate-Limiting-Logik (Brute-Force-Schutz).
 * 
 * Validiert:
 * - Prüfung auf aktive Sperren (isRateLimited)
 * - Aufzeichnung von Fehlversuchen (recordFailedAttempt)
 * - Automatische Sperrung bei Überschreitung des Limits (checkAndLockout)
 * - Korrekte Nutzung der Konfiguration (Grenzwerte)
 */
class RateLimiterTest extends TestCase
{
    private $configMock;
    private $repositoryMock;
    private $loggerMock;
    private $rateLimiter;

    /**
     * Initialisiert die Testumgebung.
     * Erstellt Mocks für alle Abhängigkeiten und konfiguriert Standardwerte.
     */
    protected function setUp(): void
    {
        // 1. Abhängigkeiten mocken
        $this->configMock = $this->createMock(ConfigService::class);
        $this->repositoryMock = $this->createMock(RateLimitInterface::class);
        $this->loggerMock = $this->createMock(SecurityLogger::class);

        // 2. Konfiguration simulieren (ReturnMap für verschiedene Keys)
        // Wir testen mit einem Limit von 3 Versuchen und 5 Minuten (300s) Sperre.
        $this->configMock->method('get')->willReturnMap([
            ['security.login_max_attempts', null, 3], 
            ['security.login_lockout_time', null, 300],
            ['security.login_attempt_window', null, 60] // Fenster: 1 Minute
        ]);

        // 3. Service instanziieren
        $this->rateLimiter = new RateLimiter(
            $this->configMock,
            $this->repositoryMock,
            $this->loggerMock
        );
    }

    /**
     * Prüft, ob isRateLimited() false zurückgibt, wenn keine Sperre aktiv ist.
     */
    public function testIsRateLimitedReturnsFalseIfNotLocked(): void
    {
        // Arrange: Repository meldet "Sperre abgelaufen" (0 oder Zeitstempel in Vergangenheit)
        $this->repositoryMock->method('getLockoutExpiry')->willReturn(0);

        // Act & Assert
        $this->assertFalse($this->rateLimiter->isRateLimited('test-user'));
    }

    /**
     * Prüft, ob isRateLimited() true zurückgibt und loggt, wenn eine Sperre aktiv ist.
     */
    public function testIsRateLimitedReturnsTrueIfLocked(): void
    {
        // Arrange: Repository meldet "Sperre aktiv bis in 5 Minuten"
        $futureTime = time() + 300;
        $this->repositoryMock->method('getLockoutExpiry')->willReturn($futureTime);

        // Expect: Der Logger muss informiert werden, dass ein gesperrter User zugreift
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('rate_limit_active');

        // Act & Assert
        $this->assertTrue($this->rateLimiter->isRateLimited('test-user'));
    }

    /**
     * Prüft, ob ein Fehlversuch korrekt aufgezeichnet wird, ohne sofort zu sperren.
     */
    public function testRecordFailedAttemptIncrementsCounter(): void
    {
        // Expect 1: Versuch muss im Repository gespeichert werden
        $this->repositoryMock->expects($this->once())
            ->method('recordAttempt')
            ->with('test-user');

        // Expect 2: Anzahl der Versuche abrufen. Wir simulieren 1 Versuch (Limit ist 3).
        $this->repositoryMock->method('getAttempts')->willReturn(1);
        
        // Expect 3: KEIN Lockout, da Limit nicht erreicht
        $this->repositoryMock->expects($this->never())->method('setLockout');

        // Act
        $this->rateLimiter->recordFailedAttempt('test-user');
    }

    /**
     * Prüft, ob eine Sperre verhängt wird, wenn das Limit erreicht ist.
     */
    public function testRecordFailedAttemptTriggersLockoutWhenLimitReached(): void
    {
        // Arrange: Repository meldet "3 Versuche" (Limit erreicht)
        $this->repositoryMock->method('getAttempts')->willReturn(3);

        // Expect 1: Lockout muss gesetzt werden (300 Sekunden aus Config)
        $this->repositoryMock->expects($this->once())
            ->method('setLockout')
            ->with('test-user', 300);

        // Expect 2: Logger muss über die neue Sperre informiert werden
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('rate_limit_lockout');

        // Act
        $this->rateLimiter->recordFailedAttempt('test-user');
    }
}