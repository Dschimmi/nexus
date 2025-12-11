<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use MrWo\Nexus\Service\AuthenticationService;
use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\SecurityLogger;
use MrWo\Nexus\Repository\UserRepositoryInterface;
use MrWo\Nexus\Entity\User;
use MrWo\Nexus\Service\SessionBag;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Testet den AuthenticationService.
 * @coversDefaultClass \MrWo\Nexus\Service\AuthenticationService
 */
class AuthenticationServiceTest extends TestCase
{
    private $sessionMock;
    private $securityBagMock;
    private $userRepoMock;
    private $loggerMock;
    private $authService;

    // Test-Konstanten
    private const TEST_USERNAME = 'admin';
    private const TEST_EMAIL = 'admin@example.com';
    private const TEST_PASSWORD = 'secret';

    private User $mockUser;

    protected function setUp(): void
    {
        // 1. Initialisierung aller Mocks
        $this->sessionMock = $this->createMock(SessionService::class);
        $this->securityBagMock = $this->createMock(SessionBag::class);
        $this->userRepoMock = $this->createMock(UserRepositoryInterface::class);
        $this->loggerMock = $this->createMock(SecurityLogger::class);

        // 2. SessionService muss den Security Bag zurückgeben
        $this->sessionMock->method('getBag')
            ->with('security')
            ->willReturn($this->securityBagMock);
            
        // 3. User-Entity mit korrektem Hash und 7 Argumenten erstellen
        // Der Hash muss ein gültiger Argon2ID-Hash des Testpassworts sein.
        $passwordHash = password_hash(self::TEST_PASSWORD, PASSWORD_ARGON2ID);
        
        $this->mockUser = new User(
            'root',                      // ID
            self::TEST_USERNAME,         // Username
            self::TEST_EMAIL,            // Email
            $passwordHash,               // PasswordHash (echt gehasht für password_verify)
            'System',                    // Group
            'Administrator',             // Role
            1                            // AuthVersion
        );

        // 4. Service mit den korrigierten Mocks instanziieren
        $this->authService = new AuthenticationService(
            $this->sessionMock,
            $this->userRepoMock,
            $this->loggerMock
        );
    }

    // --- Login Tests ---

    /**
     * Testet erfolgreichen Login mit Benutzernamen.
     * @covers ::login
     */
    public function testLoginSuccessWithUsername(): void
    {
        // Erwartung: Repo findet User
        $this->userRepoMock->expects($this->once())
            ->method('findByIdentifier')
            ->with(self::TEST_USERNAME)
            ->willReturn($this->mockUser);

        // Erwartung: Session-ID muss rotiert werden
        $this->sessionMock->expects($this->once())
            ->method('migrate')
            ->with(true);

        // Erwartung: User-Daten landen im Bag
        $this->securityBagMock->expects($this->once())
            ->method('set')
            ->with('user', $this->mockUser->toArray());

        // Erwartung: Login-Success wird geloggt
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('auth_login_success', $this->isType('array'));

        $result = $this->authService->login(self::TEST_USERNAME, self::TEST_PASSWORD);

        $this->assertTrue($result);
    }

    /**
     * Testet erfolgreichen Login mit E-Mail.
     * @covers ::login
     */
    public function testLoginSuccessWithEmail(): void
    {
        // Erwartung: Repo findet User (mit Email)
        $this->userRepoMock->expects($this->once())
            ->method('findByIdentifier')
            ->with(self::TEST_EMAIL)
            ->willReturn($this->mockUser);

        // Erwartung: Session-ID muss rotiert werden
        $this->sessionMock->expects($this->once())->method('migrate')->with(true);
        // Erwartung: User-Daten landen im Bag
        $this->securityBagMock->expects($this->once())->method('set');
        // Erwartung: Login-Success wird geloggt
        $this->loggerMock->expects($this->once())->method('log');

        $result = $this->authService->login(self::TEST_EMAIL, self::TEST_PASSWORD);

        $this->assertTrue($result);
    }

    /**
     * Testet fehlgeschlagenen Login wegen falschem Passwort.
     * @covers ::login
     */
    public function testLoginFailureWrongPassword(): void
    {
        // Erwartung: Repo findet User
        $this->userRepoMock->expects($this->once())
            ->method('findByIdentifier')
            ->willReturn($this->mockUser);
            
        // Erwartung: KEINE Migration, KEIN Setzen von Daten
        $this->sessionMock->expects($this->never())->method('migrate');
        $this->securityBagMock->expects($this->never())->method('set');

        // Erwartung: Login-Failure wird geloggt (invalid_password)
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('auth_login_failure', $this->callback(function ($context) {
                return $context['reason'] === 'invalid_password';
            }));

        $result = $this->authService->login(self::TEST_USERNAME, 'wrong_password');

        $this->assertFalse($result);
    }

    /**
     * Testet fehlgeschlagenen Login, da Benutzer nicht gefunden.
     * @covers ::login
     */
    public function testLoginFailureUnknownUser(): void
    {
        // Erwartung: Repo findet KEINEN User
        $this->userRepoMock->expects($this->once())
            ->method('findByIdentifier')
            ->willReturn(null);
            
        // Erwartung: Logger wird aufgerufen (user_not_found)
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('auth_login_failure', $this->callback(function ($context) {
                return $context['reason'] === 'user_not_found';
            }));

        $result = $this->authService->login('unknown', 'secret');
        $this->assertFalse($result);
    }

    // --- Logout Tests ---

    /**
     * Testet erfolgreiches Logout.
     * @covers ::logout
     */
    public function testLogout(): void
    {
        // Mock-Daten für getUser()
        $adminUserArray = $this->mockUser->toArray();
        $this->securityBagMock->method('get')->willReturn($adminUserArray);

        // 1. Erwartung: Logout wird geloggt
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('auth_logout', $this->isType('array'));

        // 2. Erwartung: Security Bag wird geleert
        $this->securityBagMock->expects($this->once())
            ->method('clear');

        // 3. Erwartung: Session ID wird migriert
        $this->sessionMock->expects($this->once())
            ->method('migrate')
            ->with(true);

        $this->authService->logout();
    }

    // --- Get User Tests ---

    /**
     * Testet das Laden des eingeloggten Benutzers.
     * @covers ::getUser
     */
    public function testGetUser(): void
    {
        // Mock-Daten (muss Array sein, da getUser() Array zurückgibt)
        $userArray = $this->mockUser->toArray();

        // Erwartung: Bag wird gefragt
        $this->securityBagMock->expects($this->once())
            ->method('get')
            ->with('user')
            ->willReturn($userArray);

        $result = $this->authService->getUser();

        $this->assertEquals($userArray, $result);
    }

    // --- Is Admin Tests ---

    /**
     * Testet isAdmin, wenn der Benutzer Admin-Rechte hat.
     * @covers ::isAdmin
     */
    public function testIsAdminReturnsTrue(): void
    {
        $adminUser = ['group' => 'System', 'role' => 'Administrator'];

        $this->securityBagMock->method('get')
            ->willReturn($adminUser);

        $this->assertTrue($this->authService->isAdmin());
    }

    /**
     * Testet isAdmin, wenn der Benutzer keine Admin-Rechte hat.
     * @covers ::isAdmin
     */
    public function testIsAdminReturnsFalse(): void
    {
        $normalUser = ['group' => 'User', 'role' => 'Editor'];

        $this->securityBagMock->method('get')
            ->willReturn($normalUser);

        $this->assertFalse($this->authService->isAdmin());
    }

    /**
     * Testet isAdmin, wenn kein Benutzer eingeloggt ist.
     * @covers ::isAdmin
     */
    public function testIsAdminReturnsFalseWhenNotLoggedIn(): void
    {
        $this->securityBagMock->method('get')
            ->willReturn(null);

        $this->assertFalse($this->authService->isAdmin());
    }
}