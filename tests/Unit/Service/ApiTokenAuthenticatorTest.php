<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Infrastructure\Security\ApiTokenAuthenticator;
use MrWo\Nexus\Repository\ApiTokenRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Testet die API-Token Authentifizierung.
 * 
 * Validiert:
 * - Korrekte Extraktion des Bearer-Tokens aus dem Header
 * - Ablehnung fehlender oder falsch formatierter Header
 * - Delegation der Prüfung an das Repository
 */
class ApiTokenAuthenticatorTest extends TestCase
{
    private $repositoryMock;
    private $authenticator;

    /**
     * Bereitet die Testumgebung vor.
     */
    protected function setUp(): void
    {
        // 1. Repository Mocken
        $this->repositoryMock = $this->createMock(ApiTokenRepositoryInterface::class);

        // 2. Service instanziieren
        $this->authenticator = new ApiTokenAuthenticator($this->repositoryMock);
    }

    /**
     * Prüft, ob false zurückgegeben wird, wenn der Authorization-Header fehlt.
     */
    public function testValidateReturnsFalseIfHeaderMissing(): void
    {
        $request = new Request(); // Kein Header
        
        // Repo darf gar nicht gefragt werden
        $this->repositoryMock->expects($this->never())->method('isValid');

        $this->assertFalse($this->authenticator->validate($request));
    }

    /**
     * Prüft, ob false zurückgegeben wird, wenn der Header das falsche Format hat (kein Bearer).
     */
    public function testValidateReturnsFalseIfHeaderMalformed(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Basic user:pass');

        $this->repositoryMock->expects($this->never())->method('isValid');

        $this->assertFalse($this->authenticator->validate($request));
    }

    /**
     * Prüft, ob das Token extrahiert und an das Repository übergeben wird.
     * Szenario: Token ist gültig.
     */
    public function testValidateReturnsTrueIfTokenValid(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer valid-token-123');

        // Expect: Repo wird mit dem extrahierten Token aufgerufen und sagt JA
        $this->repositoryMock->expects($this->once())
            ->method('isValid')
            ->with('valid-token-123')
            ->willReturn(true);

        $this->assertTrue($this->authenticator->validate($request));
    }

    /**
     * Prüft, ob false zurückgegeben wird, wenn das Repository das Token ablehnt.
     */
    public function testValidateReturnsFalseIfTokenInvalid(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer invalid-token');

        // Expect: Repo sagt NEIN
        $this->repositoryMock->method('isValid')->willReturn(false);

        $this->assertFalse($this->authenticator->validate($request));
    }
}