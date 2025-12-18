<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use MrWo\Nexus\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Testet die Sicherheits-Barrieren (Firewall).
 */
class SecurityBoundaryTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test');
        $_SESSION = []; // Reset

        // Mock Environment
        $_ENV['ADMIN_USER'] = 'admin';
        $_ENV['ADMIN_EMAIL'] = 'admin@example.com';
        $_ENV['ADMIN_PASSWORD_HASH'] = '$argon2id$...'; // Egal für diesen Test
        $_ENV['APP_SECRET'] = 'test';
    }

    /**
     * Prüft, ob nicht-öffentliche Seiten ohne Login umleiten (oder 403).
     * Der AdminController leitet auf /admin/login um (bzw. zeigt Login-Formular auf /admin).
     */
    public function testAdminAccessDeniedForGuests(): void
    {
        // Wir rufen eine geschützte Methode auf, z.B. /admin/pages (ListPages)
        // Die Route /admin selbst ist public (zeigt Login), aber /admin/pages ist geschützt?
        // Check AdminController: index() und login() sind #[IsPublic].
        // listPages() ist NICHT #[IsPublic].
        
        $request = Request::create('/admin/pages', 'GET');
        $response = $this->kernel->handleRequest($request);

        // Erwartung: Redirect auf /admin (Login)
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/admin', $response->headers->get('Location'));
    }

    /**
     * Prüft, ob Zugriff mit Session erlaubt ist.
     * @runInSeparateProcess
     */
    public function testAdminAccessAllowedForLoggedInUser(): void
    {

        // Trick: Wir starten die Session manuell im Test, DAMIT der SessionService denkt, sie läuft schon.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Session manipulieren (Login simulieren)
        // Wir schreiben direkt in den Security-Bag-Struktur, wie SessionService es erwartet.
        $_SESSION['security'] = [
            'user' => [
                'username' => 'admin',
                'role' => 'Administrator',
                'group' => 'System',
                'auth_version' => 1
            ]
        ];

        // 2. Request an geschützte Ressource
        $request = Request::create('/admin/pages', 'GET');
        
        // Wir müssen Cookies simulieren, damit PHP die Session findet?
        // Nein, im CLI nutzt PHPUnit $_SESSION direkt, wenn wir session_start() mocken oder den Handler.
        // Unser SessionService startet session_start(). Wenn $_SESSION schon Daten hat, bleiben die.
        
        $response = $this->kernel->handleRequest($request);
        
        // Erwartung: 200 OK (Liste)
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Seiten verwalten', $response->getContent());
    }
}