<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use MrWo\Nexus\Controller\LanguageController;
use MrWo\Nexus\Infrastructure\Session\SessionService;
use MrWo\Nexus\Infrastructure\Session\SessionBag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Testet den LanguageController.
 */
class LanguageControllerTest extends TestCase
{
    private $sessionMock;
    private $attributeBagMock;
    private $controller;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(SessionService::class);
        $this->attributeBagMock = $this->createMock(SessionBag::class);

        $this->sessionMock->method('getBag')
            ->with('attributes')
            ->willReturn($this->attributeBagMock);

        $this->controller = new LanguageController($this->sessionMock);
    }

    /**
     * Prüft, ob eine valide Sprache in der Session gespeichert wird.
     */
    public function testSwitchUpdatesSessionAndRedirects(): void
    {
        // Expect: Session wird gesetzt
        $this->attributeBagMock->expects($this->once())
            ->method('set')
            ->with('locale', 'en');

        $request = new Request([], ['lang' => 'en']); // POST params
        $request->headers->set('referer', '/previous');

        $response = $this->controller->switch($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/previous', $response->getTargetUrl());
    }

    /**
     * Prüft, ob ungültige Sprachen ignoriert werden.
     */
    public function testSwitchIgnoresInvalidLocale(): void
    {
        // Expect: Session wird NICHT gesetzt
        $this->attributeBagMock->expects($this->never())->method('set');

        $request = new Request([], ['lang' => 'fr']); // 'fr' ist nicht in Whitelist
        
        $response = $this->controller->switch($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * Prüft den Fallback auf '/', wenn kein Referer da ist.
     */
    public function testSwitchFallbacksToHomeWithoutReferer(): void
    {
        $request = new Request([], ['lang' => 'de']);
        // Kein Referer Header

        $response = $this->controller->switch($request);

        $this->assertEquals('/', $response->getTargetUrl());
    }
}