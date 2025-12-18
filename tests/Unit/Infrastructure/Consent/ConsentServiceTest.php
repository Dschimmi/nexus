<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Consent;

use MrWo\Nexus\Infrastructure\Consent\ConsentService;
use MrWo\Nexus\Infrastructure\Session\SessionService;
use MrWo\Nexus\Infrastructure\Session\SessionBag;
use PHPUnit\Framework\TestCase;

/**
 * Testet den ConsentService.
 * Prüft das Speichern und Lesen von Zustimmungen in der Session.
 */
class ConsentServiceTest extends TestCase
{
    private $sessionMock;
    private $attributeBagMock;
    private $service;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(SessionService::class);
        $this->attributeBagMock = $this->createMock(SessionBag::class);

        $this->sessionMock->method('getBag')
            ->with('attributes')
            ->willReturn($this->attributeBagMock);

        $this->service = new ConsentService($this->sessionMock);
    }

    /**
     * Prüft, ob grantConsent den Wert 'true' in der Session speichert.
     */
    public function testGrantConsentSavesTrue(): void
    {
        // 1. Lesen (aktueller Zustand)
        $this->attributeBagMock->method('get')
            ->with('user_consents', [])
            ->willReturn([]);

        // 2. Schreiben (neuer Zustand)
        $this->attributeBagMock->expects($this->once())
            ->method('set')
            ->with('user_consents', ['marketing' => true]);

        $this->service->grantConsent('marketing');
    }

    /**
     * Prüft, ob revokeConsent den Wert 'false' speichert.
     */
    public function testRevokeConsentSavesFalse(): void
    {
        // Ausgangslage: Marketing ist an
        $this->attributeBagMock->method('get')->willReturn(['marketing' => true]);

        // Erwartung: Marketing wird aus
        $this->attributeBagMock->expects($this->once())
            ->method('set')
            ->with('user_consents', ['marketing' => false]);

        $this->service->revokeConsent('marketing');
    }

    /**
     * Prüft, ob hasConsent korrekt zurückgibt.
     */
    public function testHasConsentReturnsValue(): void
    {
        $this->attributeBagMock->method('get')->willReturn(['marketing' => true]);

        $this->assertTrue($this->service->hasConsent('marketing'));
        $this->assertFalse($this->service->hasConsent('statistics')); // Nicht gesetzt
    }
}