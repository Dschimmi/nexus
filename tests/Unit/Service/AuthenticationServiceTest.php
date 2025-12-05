<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\AuthenticationService;
use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\SessionBag;
use PHPUnit\Framework\TestCase;

class AuthenticationServiceTest extends TestCase
{
    private $sessionMock;
    private $securityBagMock;
    private $authService;

    private string $adminUser = 'admin';
    private string $adminEmail = 'admin@example.com';
    // Hash für 'secret'
    private string $adminPassHash = '$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$GlX...'; 

    protected function setUp(): void
    {
        // 1. Wir mocken den SessionService
        $this->sessionMock = $this->createMock(SessionService::class);
        
        // 2. Wir mocken den Security-Bag (das ist neu!)
        $this->securityBagMock = $this->createMock(SessionBag::class);

        // 3. Wir bringen dem SessionService bei: 
        // "Wenn jemand getBag('security') aufruft, gib den Mock zurück."
        $this->sessionMock->method('getBag')
            ->with('security')
            ->willReturn($this->securityBagMock);

        // Wir brauchen einen validen Hash für den Test
        $this->adminPassHash = password_hash('secret', PASSWORD_ARGON2ID);

        $this->authService = new AuthenticationService(
            $this->sessionMock,
            $this->adminUser,
            $this->adminEmail,
            $this->adminPassHash
        );
    }

    public function testLoginSuccessWithUsername(): void
    {
        // Erwartung: Session-ID muss rotiert werden (migrate)
        $this->sessionMock->expects($this->once())
            ->method('migrate')
            ->with(true);

        // Erwartung: User-Daten landen im Bag
        $this->securityBagMock->expects($this->once())
            ->method('set')
            ->with('user', $this->callback(function ($user) {
                return $user['username'] === 'admin' 
                    && $user['role'] === 'Administrator';
            }));

        $result = $this->authService->login('admin', 'secret');

        $this->assertTrue($result);
    }

    public function testLoginSuccessWithEmail(): void
    {
        // Auch hier Migration erwarten
        $this->sessionMock->expects($this->once())->method('migrate');

        $result = $this->authService->login('admin@example.com', 'secret');

        $this->assertTrue($result);
    }

    public function testLoginFailureWrongPassword(): void
    {
        // Erwartung: KEINE Migration, KEIN Setzen von Daten
        $this->sessionMock->expects($this->never())->method('migrate');
        $this->securityBagMock->expects($this->never())->method('set');

        $result = $this->authService->login('admin', 'wrong');

        $this->assertFalse($result);
    }

    public function testLoginFailureUnknownUser(): void
    {
        $result = $this->authService->login('unknown', 'secret');
        $this->assertFalse($result);
    }

    public function testLogout(): void
    {
        // Erwartung: Invalidate muss aufgerufen werden
        $this->sessionMock->expects($this->once())
            ->method('invalidate');

        $this->authService->logout();
    }

    public function testGetUser(): void
    {
        // Mock-Daten
        $user = ['id' => 'root', 'username' => 'admin'];

        // Erwartung: Service fragt den Bag
        $this->securityBagMock->expects($this->once())
            ->method('get')
            ->with('user')
            ->willReturn($user);

        $result = $this->authService->getUser();

        $this->assertEquals($user, $result);
    }

    public function testIsAdminReturnsTrue(): void
    {
        $adminUser = ['group' => 'System', 'role' => 'Administrator'];

        $this->securityBagMock->method('get')
            ->willReturn($adminUser);

        $this->assertTrue($this->authService->isAdmin());
    }

    public function testIsAdminReturnsFalse(): void
    {
        $normalUser = ['group' => 'User', 'role' => 'Editor'];

        $this->securityBagMock->method('get')
            ->willReturn($normalUser);

        $this->assertFalse($this->authService->isAdmin());
    }
}