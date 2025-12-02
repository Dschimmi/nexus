<?php

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\AuthenticationService;
use MrWo\Nexus\Service\SessionService;
use PHPUnit\Framework\TestCase;

class AuthenticationServiceTest extends TestCase
{
    private $sessionMock;
    private string $testUser = 'admin';
    private string $testEmail = 'admin@example.com';
    private string $testPassword = 'geheimnis';
    private string $testHash;

    protected function setUp(): void
    {
        // 1. Session Mock erstellen
        $this->sessionMock = $this->createMock(SessionService::class);

        // 2. Einen validen Hash für das Test-Passwort generieren
        // Da der Service password_verify() nutzt, muss der Hash echt sein.
        $this->testHash = password_hash($this->testPassword, PASSWORD_DEFAULT);
    }

    private function createService(): AuthenticationService
    {
        return new AuthenticationService(
            $this->sessionMock,
            $this->testUser,
            $this->testEmail,
            $this->testHash
        );
    }

    public function testLoginSuccessWithUsername()
    {
        $service = $this->createService();

        // Erwartung: Session-ID muss neu generiert werden (Sicherheit!)
        $this->sessionMock->expects($this->once())
            ->method('regenerate');

        // Erwartung: User-Daten müssen in die Session geschrieben werden
        $this->sessionMock->expects($this->once())
            ->method('set')
            ->with('user', $this->callback(function($userArray) {
                return $userArray['username'] === $this->testUser
                    && $userArray['role'] === 'Administrator'
                    && $userArray['group'] === 'System'; // AuthenticationService und die Methode isAdmin() verlassen sich zwingend auf die Gruppe
            }));

        // Login mit korrektem User und Passwort
        $result = $service->login($this->testUser, $this->testPassword);

        $this->assertTrue($result, 'Login mit korrektem Username sollte true zurückgeben.');
    }

    public function testLoginSuccessWithEmail()
    {
        $service = $this->createService();

        // Login mit korrekter E-Mail
        $result = $service->login($this->testEmail, $this->testPassword);

        $this->assertTrue($result, 'Login mit korrekter E-Mail sollte true zurückgeben.');
    }

    public function testLoginFailureWithWrongPassword()
    {
        $service = $this->createService();

        // Erwartung: Es darf NICHTS in die Session geschrieben werden
        $this->sessionMock->expects($this->never())->method('set');
        $this->sessionMock->expects($this->never())->method('regenerate');

        // Login mit falschem Passwort
        $result = $service->login($this->testUser, 'falschesPasswort');

        $this->assertFalse($result, 'Login mit falschem Passwort muss false zurückgeben.');
    }

    public function testLoginFailureWithUnknownUser()
    {
        $service = $this->createService();

        $result = $service->login('unbekannt', $this->testPassword);

        $this->assertFalse($result, 'Login mit unbekanntem User muss false zurückgeben.');
    }

    public function testLogout()
    {
        $service = $this->createService();

        // Erwartung: User entfernen UND Session regenerieren
        $this->sessionMock->expects($this->once())->method('remove')->with('user');
        $this->sessionMock->expects($this->once())->method('regenerate');

        $service->logout();
    }

    public function testIsAdminReturnsTrueIfSessionHasAdminData()
    {
        $service = $this->createService();

        // Wir simulieren, dass die Session einen Admin zurückgibt
        $this->sessionMock->method('get')->willReturn([
            'group' => 'System',
            'role' => 'Administrator'
        ]);

        $this->assertTrue($service->isAdmin());
    }

    public function testIsAdminReturnsFalseIfSessionEmpty()
    {
        $service = $this->createService();

        // Wir simulieren, dass niemand eingeloggt ist (null)
        $this->sessionMock->method('get')->willReturn(null);

        $this->assertFalse($service->isAdmin());
    }
}