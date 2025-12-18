<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use MrWo\Nexus\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Testet Stateful-Szenarien über mehrere Requests hinweg.
 */
class UserJourneyTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test');
        $_SESSION = [];
    }

    /**
     * @runInSeparateProcess
     */
    public function testLanguageAndConsentFlow(): void
    {
        // 1. Session starten & ID sichern
        if (session_status() === PHP_SESSION_NONE) session_start();
        $sessionId = session_id(); 
        session_write_close();

        // 2. Sprache setzen (POST)
        $req1 = Request::create('/language/switch', 'POST', ['lang' => 'en']);
        $req1->cookies->set(session_name(), $sessionId);
        $this->kernel->handleRequest($req1);
        
        // 3. Consent geben
        $req2 = Request::create('/consent/accept', 'GET');
        $req2->cookies->set(session_name(), $sessionId);
        $this->kernel->handleRequest($req2);

        // 4. Startseite prüfen (muss Englisch sein)
        $req3 = Request::create('/', 'GET');
        $req3->cookies->set(session_name(), $sessionId);
        $resp3 = $this->kernel->handleRequest($req3);

        $this->assertEquals(200, $resp3->getStatusCode());
        // Prüft auf englischen Text ("Modern Web Framework" vs "Modernes Web-Framework")
        $this->assertStringContainsString('Modern Web Framework', $resp3->getContent(), 'Homepage should be English');
    }
}