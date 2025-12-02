<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\TranslatorService;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * Testet den TranslatorService isoliert.
 * Nutzt vfsStream, um eine virtuelle Sprachdatei zu simulieren.
 */
class TranslatorServiceTest extends TestCase
{
    private $sessionMock;
    private $vfsRoot;

    protected function setUp(): void
    {
        // SessionService mocken (wird aktuell nur injiziert, aber nicht genutzt)
        $this->sessionMock = $this->createMock(SessionService::class);
        
        // Wir simulieren das Dateisystem: root/translations/de.php
        // Hier definieren wir Test-Daten, die NICHTS mit der echten de.php zu tun haben.
        $this->vfsRoot = vfsStream::setup('root', 0777, [
            'translations' => [
                'de.php' => '<?php return [
                    "test.simple" => "Einfacher Text",
                    "test.param" => "Hallo %name%",
                    "test.multi" => "Seite %cur% von %max%"
                ];'
            ]
        ]);
    }

    /**
     * Helper-Methode zum Erstellen des Services mit virtuellem Pfad.
     */
    private function createService(): TranslatorService
    {
        // Wir übergeben die URL unseres virtuellen Dateisystems als ProjectDir.
        // Der Service sucht dann in vfs://root/translations/de.php
        return new TranslatorService($this->sessionMock, vfsStream::url('root'));
    }

    /**
     * Prüft, ob der Key zurückgegeben wird, wenn keine Übersetzung existiert.
     */
    public function testTranslateReturnsKeyIfTranslationMissing()
    {
        $service = $this->createService();
        $missingKey = 'nicht.vorhanden';
        
        $this->assertEquals($missingKey, $service->translate($missingKey));
    }

    /**
     * Prüft, ob ein einfacher Text korrekt aus der virtuellen Datei geladen wird.
     */
    public function testTranslateReturnsSimpleText()
    {
        $service = $this->createService();
        
        $this->assertEquals('Einfacher Text', $service->translate('test.simple'));
    }

    /**
     * Prüft die Ersetzung eines einzelnen Platzhalters.
     */
    public function testTranslateReplacesPlaceholders()
    {
        $service = $this->createService();
        
        $result = $service->translate('test.param', ['%name%' => 'Nexus']);
        
        $this->assertEquals('Hallo Nexus', $result);
    }

    /**
     * Prüft die Ersetzung mehrerer Platzhalter.
     */
    public function testTranslateHandlesMultiplePlaceholders()
    {
        $service = $this->createService();
        
        $result = $service->translate('test.multi', [
            '%cur%' => '1', 
            '%max%' => '10'
        ]);
        
        $this->assertEquals('Seite 1 von 10', $result);
    }
    
    /**
     * Prüft, ob der Service stabil bleibt, wenn der Translations-Ordner fehlt.
     */
    public function testServiceIsRobustAgainstMissingFile()
    {
        // Wir simulieren ein leeres Root ohne Translations-Ordner
        $emptyRoot = vfsStream::setup('empty');
        
        // Wir injizieren den Pfad zu diesem leeren Ordner
        $service = new TranslatorService($this->sessionMock, vfsStream::url('empty'));
        
        // Sollte nicht abstürzen, sondern einfach den Key zurückgeben (Fallback)
        $this->assertEquals('foo', $service->translate('foo'));
    }
}