<?php

namespace MrWo\Nexus\Tests\Service;

use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\TranslatorService;
use PHPUnit\Framework\TestCase;

class TranslatorServiceTest extends TestCase
{
    public function testTranslateReturnsCorrectStringForGerman()
    {
        // 1. VORBEREITUNG (Arrange)
        
        // Wir erstellen einen "Fake" (Mock) vom SessionService.
        // Wir sagen ihm: "Wenn dich jemand fragt, gib einfach 'de' zurück."
        $sessionMock = $this->createMock(SessionService::class);
        $sessionMock->method('get')->willReturn('de');

        // Wir erstellen den echten TranslatorService, geben ihm aber den Fake-SessionService.
        // Der Translator merkt den Unterschied nicht.
        $translator = new TranslatorService($sessionMock);

        // 2. DURCHFÜHRUNG (Act)
        // Wir rufen die Methode auf, die wir testen wollen.
        // 'welcome_message' ist ein Schlüssel, den wir in translations/de.php haben.
        $result = $translator->translate('welcome_message');

        // 3. PRÜFUNG (Assert)
        // Wir behaupten: Das Ergebnis MUSS "Willkommen bei Nexus!" sein.
        // Wenn nicht, schlägt der Test fehl.
        $this->assertEquals('Willkommen bei Nexus!', $result);
    }

    public function testTranslateReturnsKeyIfNotFound()
    {
        // 1. VORBEREITUNG
        $sessionMock = $this->createMock(SessionService::class);
        $sessionMock->method('get')->willReturn('de');
        $translator = new TranslatorService($sessionMock);

        // 2. DURCHFÜHRUNG
        // Wir fragen nach einem Schlüssel, den es gar nicht gibt.
        $result = $translator->translate('dieser_schluessel_existiert_nicht');

        // 3. PRÜFUNG
        // Erwartung: Der Service gibt den Schlüssel selbst zurück.
        $this->assertEquals('dieser_schluessel_existiert_nicht', $result);
    }
}