<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use MrWo\Nexus\Controller\ConsentController;
use MrWo\Nexus\Infrastructure\Consent\ConsentService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Testet den ConsentController.
 * Prüft die Weiterleitung von Benutzeraktionen an den ConsentService.
 */
class ConsentControllerTest extends TestCase
{
    private $consentServiceMock;
    private $controller;

    protected function setUp(): void
    {
        $this->consentServiceMock = $this->createMock(ConsentService::class);
        $this->controller = new ConsentController($this->consentServiceMock);
    }

    /**
     * Prüft, ob accept() die Zustimmung speichert und zurückleitet.
     */
    public function testAcceptGrantsConsentAndRedirects(): void
    {
        // Erwartung: Service wird aufgerufen (für marketing & statistics)
        $this->consentServiceMock->expects($this->exactly(2))
            ->method('grantConsent');

        // Request mit Referer simulieren
        $request = new Request();
        $request->headers->set('referer', '/previous-page');

        $response = $this->controller->accept($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/previous-page', $response->getTargetUrl());
    }

    /**
     * Prüft, ob decline() die Zustimmung widerruft und zurückleitet.
     */
    public function testDeclineRevokesConsentAndRedirects(): void
    {
        // Erwartung: Service wird aufgerufen
        $this->consentServiceMock->expects($this->exactly(2))
            ->method('revokeConsent');

        $request = new Request(); // Kein Referer -> Fallback auf '/'
        
        $response = $this->controller->decline($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getTargetUrl());
    }
}