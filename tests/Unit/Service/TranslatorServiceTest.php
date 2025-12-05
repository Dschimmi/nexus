<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\TranslatorService;
use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\SessionBag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class TranslatorServiceTest extends TestCase
{
    private $sessionMock;
    private $attributeBagMock;
    private string $projectDir;

    protected function setUp(): void
    {
        // Virtuelles Projektverzeichnis erstellen
        $this->projectDir = sys_get_temp_dir() . '/nexus_test_' . uniqid();
        mkdir($this->projectDir . '/translations', 0777, true);

        // Dummy-Übersetzung anlegen
        $deContent = "<?php return ['welcome' => 'Willkommen', 'hello' => 'Hallo %name%'];";
        file_put_contents($this->projectDir . '/translations/de.php', $deContent);

        // Session Mocking
        $this->sessionMock = $this->createMock(SessionService::class);
        $this->attributeBagMock = $this->createMock(SessionBag::class);

        // Wenn getBag('attributes') aufgerufen wird, gib den Mock zurück
        // Wir nutzen any(), da initializeLocale vielleicht nicht immer aufgerufen wird
        $this->sessionMock->method('getBag')
            ->with('attributes')
            ->willReturn($this->attributeBagMock);
    }

    protected function tearDown(): void
    {
        // Aufräumen
        if (is_dir($this->projectDir)) {
            array_map('unlink', glob($this->projectDir . '/translations/*'));
            rmdir($this->projectDir . '/translations');
            rmdir($this->projectDir);
        }
    }

    public function testTranslateSimpleKey(): void
    {
        $translator = new TranslatorService($this->sessionMock, $this->projectDir);
        $this->assertEquals('Willkommen', $translator->translate('welcome'));
    }

    public function testTranslateWithParams(): void
    {
        $translator = new TranslatorService($this->sessionMock, $this->projectDir);
        $this->assertEquals('Hallo Max', $translator->translate('hello', ['%name%' => 'Max']));
    }

    public function testTranslateUnknownKeyReturnsKey(): void
    {
        $translator = new TranslatorService($this->sessionMock, $this->projectDir);
        $this->assertEquals('unknown.key', $translator->translate('unknown.key'));
    }

    public function testInitializeLocaleFromUrl(): void
    {
        $translator = new TranslatorService($this->sessionMock, $this->projectDir);
        
        // Request mit ?lang=en simulieren
        $request = new Request(['lang' => 'en']);

        // Erwartung: Die neue Sprache muss in der Session gespeichert werden
        $this->attributeBagMock->expects($this->once())
            ->method('set')
            ->with('locale', 'en');

        $translator->initializeLocale($request);
        
        // Da wir keine en.php haben, wird er im Fallback wieder 'de' laden oder leer bleiben,
        // aber wir testen hier primär die Logik der Umschaltung.
        $this->assertEquals('en', $translator->getLocale());
    }

    public function testInitializeLocaleFromSession(): void
    {
        // Szenario: Session sagt 'en', URL sagt nichts
        $this->attributeBagMock->method('has')->with('locale')->willReturn(true);
        $this->attributeBagMock->method('get')->with('locale')->willReturn('en');

        $translator = new TranslatorService($this->sessionMock, $this->projectDir);
        $request = new Request(); // Leer

        $translator->initializeLocale($request);

        $this->assertEquals('en', $translator->getLocale());
    }
}