<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\SessionBag;
use PHPUnit\Framework\TestCase;

class SessionServiceTest extends TestCase
{
    private SessionService $sessionService;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        
        $this->sessionService = new SessionService();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetBagCreatesNewBag(): void
    {
        $bag = $this->sessionService->getBag('test_bag');
        $this->assertInstanceOf(SessionBag::class, $bag);
        $this->assertEmpty($bag->all());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDataPersistenceBetweenBags(): void
    {
        $bag = $this->sessionService->getBag('attributes');
        $bag->set('theme', 'dark');

        $this->sessionService->start();
        $this->sessionService->save();

        $this->assertArrayHasKey('attributes', $_SESSION);
        $this->assertEquals('dark', $_SESSION['attributes']['theme']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddAndGetFlashMessages(): void
    {
        $this->sessionService->addFlash('success', 'Alles super');
        $this->sessionService->addFlash('error', 'Oje');

        $flashes = $this->sessionService->getFlashes();

        $this->assertCount(2, $flashes);
        $this->assertEquals('Alles super', $flashes['success'][0]);
        $this->assertEquals('Oje', $flashes['error'][0]);

        $flashesEmpty = $this->sessionService->getFlashes();
        $this->assertEmpty($flashesEmpty);
    }

    /**
     * @runInSeparateProcess
     */
    /**
     * @runInSeparateProcess
     */
    public function testInvalidateClearsEverything(): void
    {
        // 1. Arrange
        $this->sessionService->getBag('security')->set('user_id', 123);
        $this->sessionService->save();
        
        // 2. Act
        $this->sessionService->invalidate();

        // 3. Assert (Sofort prüfen!)
        $this->assertEmpty($_SESSION, 'Session should be empty immediately after invalidate');

        // 4. Force New Session ID (Simulate Browser behavior)
        // Im echten Browser löscht invalidate() das Cookie.
        // In PHPUnit müssen wir manuell so tun, als hätten wir keine ID mehr.
        session_id(uniqid());

        // 5. Verify Bag Reset with fresh service
        $this->sessionService = new SessionService();
        $newBag = $this->sessionService->getBag('security');
        
        $this->assertNull($newBag->get('user_id'), 'Bag should be empty/re-initialized');
    }
}