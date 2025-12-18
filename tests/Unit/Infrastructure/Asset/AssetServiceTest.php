<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Asset;

use MrWo\Nexus\Infrastructure\Asset\AssetService;
use PHPUnit\Framework\TestCase;

/**
 * Testet den AssetService.
 * Fokus: Fallback-Verhalten im Entwicklungsmodus.
 */
class AssetServiceTest extends TestCase
{
    /**
     * Setzt die Umgebungsvariablen nach jedem Test zurück.
     */
    protected function tearDown(): void
    {
        unset($_ENV['APP_ENV']);
    }

    /**
     * Prüft, ob im Development-Modus (ohne Manifest) korrekte Fallback-Pfade generiert werden.
     * Erwartet: /css/*.css, /js/*.js oder direkte Rückgabe.
     */
    public function testGetReturnsFallbackInDevMode(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $service = new AssetService();

        // CSS Fallback
        $this->assertEquals('/css/style.css', $service->get('style.css'));
        
        // JS Fallback
        $this->assertEquals('/js/app.js', $service->get('app.js'));
        
        // Image Fallback (Unverändert)
        $this->assertEquals('/logo.png', $service->get('logo.png'));
    }
}