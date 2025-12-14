<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Application\Auth\AuthenticationService;
use MrWo\Nexus\Infrastructure\Session\SessionService;
use MrWo\Nexus\Infrastructure\Session\SessionBag;
use MrWo\Nexus\Domain\User\UserRepositoryInterface;
use MrWo\Nexus\Domain\User\User;
use MrWo\Nexus\Infrastructure\Security\SecurityLogger;
use MrWo\Nexus\Infrastructure\Security\RateLimiter;
use PHPUnit\Framework\TestCase;

/**
 * Testet die Authentifizierungslogik.
 * 
 * Validiert:
 * - Zusammenspiel mit UserRepository (Domain)
 * - Session-Management (Migration, Bags)
 * - Logging (SecurityLogger)
 * - Rate Limiting
 */
class AuthenticationServiceTest extends TestCase
{
    private $sessionMock;
    private $securityBagMock;
    private $userRepoMock;
    private $loggerMock;
    private $rateLimiterMock;
    private $authService;

    // Testdaten
    private string $passwordRaw = 'secret';
    private string $passwordHash;

    protected function setUp(): void
    {
        // 1. Session Mocking
        $this->sessionMock = $this->createMock(SessionService::class);
        $this->securityBagMock = $this->createMock(SessionBag::class);
        
        $this->sessionMock->method('getBag')
            ->with('security')
            ->willReturn($this->securityBagMock);

        // 2. User Repository Mocking
        $this->userRepoMock = $this->createMock(UserRepositoryInterface::class);

        // 3. Security Logger Mocking
        $this->loggerMock = $this->createMock(SecurityLogger::class);

        // 4. Rate Limiter Mocking
        $this->rateLimiterMock = $this->createMock(RateLimiter::class);

        // Hash vorbereiten
        $this->passwordHash = password_hash($this->passwordRaw, PASSWORD_ARGON2ID);

        // Service instanziieren
        $this->authService = new AuthenticationService(
            $this->sessionMock,
            $this->userRepoMock,
            $this->loggerMock,
            $this->rateLimiterMock
        );
    }

    /**
     * Prüft erfolgreichen Login.
     */
    public function testLoginSuccess(): void
    {
        // Arrange: User existiert und Passwort stimmt
        $user = new User('1', 'admin', 'admin@test.com', $this->passwordHash, 'System', 'Admin');
        
        // Repo liefert User
        $this->userRepoMock->expects($this->once())
            ->method('findByIdentifier')
            ->with('admin')
            ->willReturn($user);

        // Rate Limiter erlaubt Zugriff
        $this->rateLimiterMock->method('isRateLimited')->willReturn(false);

        // Logger erwartet Success-Log
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('auth_login_success');

        // Session erwartet Migration
        $this->sessionMock->expects($this->once())->method('migrate')->with(true);

        // Act
        $result = $this->authService->login('admin', $this->passwordRaw);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Prüft Login-Fehler bei falschem Passwort.
     */
    public function testLoginFailureWrongPassword(): void
    {
        // Arrange: User existiert
        $user = new User('1', 'admin', 'admin@test.com', $this->passwordHash, 'System', 'Admin');
        
        $this->userRepoMock->method('findByIdentifier')->willReturn($user);
        $this->rateLimiterMock->method('isRateLimited')->willReturn(false);

        // Expect: Logger failure & Rate Limiter count up
        $this->loggerMock->expects($this->once())->method('log')->with('auth_login_failure');
        $this->rateLimiterMock->expects($this->exactly(2))->method('recordFailedAttempt'); // IP + User

        // Act
        $result = $this->authService->login('admin', 'wrong_pass');

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Prüft Blockade durch Rate Limiter.
     */
    public function testLoginBlockedByRateLimiter(): void
    {
        // Arrange: Rate Limiter sagt JA (gesperrt)
        $this->rateLimiterMock->expects($this->once())
            ->method('isRateLimited')
            ->willReturn(true);

        // Expect: Repo wird NICHT gefragt (Performance/Security)
        $this->userRepoMock->expects($this->never())->method('findByIdentifier');

        // Act
        $result = $this->authService->login('admin', 'secret');

        // Assert
        $this->assertFalse($result);
    }
}