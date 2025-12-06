<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\TranslatorService;
use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\SessionBag;
use PHPUnit\Framework\TestCase;

/**
 * Testet die Funktionalität des TranslatorService.
 * Fokus: Laden der Sprache aus der Session und Fallback-Verhalten.
 */
class TranslatorServiceTest extends TestCase
{
    private $sessionMock;
    private $attributeBagMock;
    private string $projectDir;

    /**
     * Bereitet die Testumgebung vor.
     * Erstellt temporäre Sprachdateien und mockt den SessionService.
     */
    protected function setUp(): void
    {
        // 1. Temporäres Verzeichnis für Übersetzungen anlegen
        $this->projectDir = sys_get_temp_dir() . '/nexus_test_' . uniqid();
        mkdir($this->projectDir . '/translations', 0777, true);

        // 2. Dummy-Sprachdateien erstellen
        $deContent = "<?php return ['welcome' => 'Willkommen', 'bye' => 'Tschüss'];";
        file_put_contents($this->projectDir . '/translations/de.php', $deContent);
        
        $enContent = "<?php return ['welcome' => 'Welcome', 'bye' => 'Bye'];";
        file_put_contents($this->projectDir . '/translations/en.php', $enContent);

        // 3. Session-Service und Attribute-Bag mocken
        $this->sessionMock = $this->createMock(SessionService::class);
        $this->attributeBagMock = $this->createMock(SessionBag::class);

        // Dem Session-Service beibringen, unseren Bag-Mock zurückzugeben
        $this->sessionMock->method('getBag')
            ->with('attributes')
            ->willReturn($this->attributeBagMock);
    }

    /**
     * Räumt nach dem Test auf (löscht temporäre Dateien).
     */
    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            array_map('unlink', glob($this->projectDir . '/translations/*'));
            rmdir($this->projectDir . '/translations');
            rmdir($this->projectDir);
        }
    }

    /**
     * Testet, ob standardmäßig 'de' geladen wird, wenn die Session leer ist.
     */
    public function testDefaultLocaleIsDe(): void
    {
        // Arrange: Session hat keinen 'locale' Key
        $this->attributeBagMock->method('has')->with('locale')->willReturn(false);

        // Act: Service instanziieren
        $translator = new TranslatorService($this->sessionMock, $this->projectDir);

        // Assert: Sprache ist 'de' und Übersetzung ist deutsch
        $this->assertEquals('de', $translator->getLocale());
        $this->assertEquals('Willkommen', $translator->translate('welcome'));
    }

    /**
     * Testet, ob die Sprache aus der Session geladen wird.
     */
    public function testLocaleFromSession(): void
    {
        // Arrange: Session hat 'locale' => 'en'
        $this->attributeBagMock->method('has')->with('locale')->willReturn(true);
        $this->attributeBagMock->method('get')->with('locale')->willReturn('en');

        // Act
        $translator = new TranslatorService($this->sessionMock, $this->projectDir);

        // Assert: Sprache ist 'en' und Übersetzung ist englisch
        $this->assertEquals('en', $translator->getLocale());
        $this->assertEquals('Welcome', $translator->translate('welcome'));
    }

    /**
     * Testet die Ersetzung von Platzhaltern (%name%).
     */
    public function testTranslateWithParams(): void
    {
        // Setup für diesen Test: Wir brauchen einen Platzhalter in der Datei
        $deContent = "<?php return ['hello' => 'Hallo %name%'];";
        file_put_contents($this->projectDir . '/translations/de.php', $deContent);
        
        $this->attributeBagMock->method('has')->willReturn(false); // Default DE

        $translator = new TranslatorService($this->sessionMock, $this->projectDir);

        // Act & Assert
        $this->assertEquals('Hallo Max', $translator->translate('hello', ['%name%' => 'Max']));
    }
}