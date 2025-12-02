<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\TranslatorService;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * Testet den TranslatorService isoliert.
 * 
 * Nutzt vfsStream (Virtual File System), um Sprachdateien im Arbeitsspeicher 
 * zu simulieren. Dies entkoppelt den Test von der echten `translations/de.php`
 * und verhindert Fehler auf CI-Systemen durch fehlende Dateien.
 */
class TranslatorServiceTest extends TestCase
{
    private $sessionMock;
    private $vfsRoot;

    /**
     * Bereitet die Testumgebung vor.
     * Erstellt ein virtuelles Dateisystem 'root' mit einem Unterordner 'translations'
     * und einer validen PHP-Array-Datei 'de.php'.
     */
    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(SessionService::class);
        
        // 1. Setup vfsStream mit expliziten Berechtigungen (0777), um Zugriffsfehler zu vermeiden.
        $this->vfsRoot = vfsStream::setup('root', 0777);
        
        // 2. Wir erstellen die Struktur manuell, um sicherzustellen, dass der Ordner existiert.
        $translationsDir = vfsStream::newDirectory('translations', 0777)->at($this->vfsRoot);
        
        // 3. Inhalt der virtuellen Sprachdatei definieren.
        // WICHTIG: Der String muss valides PHP sein. Wir nutzen explizite Zeilenumbrüche (\n),
        // damit 'require' auch auf strikten Linux-Systemen (CI) den Datei-Header korrekt parst.
        $phpContent = "<?php\n return [\n" .
            "    'test.simple' => 'Einfacher Text',\n" .
            "    'test.param' => 'Hallo %name%',\n" .
            "    'test.multi' => 'Seite %cur% von %max%'\n" .
            "];";

        // Datei im virtuellen Ordner anlegen
        vfsStream::newFile('de.php', 0777)
            ->withContent($phpContent)
            ->at($translationsDir);
    }

    /**
     * Helper-Methode zum Erstellen des Services.
     * Injiziert die URL des virtuellen Dateisystems (vfs://root) als Projektpfad.
     */
    private function createService(): TranslatorService
    {
        return new TranslatorService($this->sessionMock, vfsStream::url('root'));
    }

    /**
     * Debug-Test: Prüft die Integrität der virtuellen Umgebung.
     * Stellt sicher, dass vfsStream korrekt konfiguriert ist und die Datei via 'require'
     * geladen werden kann. Wenn dieser Test fehlschlägt, liegt das Problem nicht am Service.
     */
    public function testVirtualFileIsReadable()
    {
        $path = vfsStream::url('root/translations/de.php');
        
        // Existenzprüfung
        $this->assertFileExists($path, 'Die virtuelle Sprachdatei wurde nicht angelegt.');
        
        // Lesbarkeitsprüfung (Simuliert das Verhalten von TranslatorService::loadTranslations)
        $data = require $path;
        
        $this->assertIsArray($data, 'Die virtuelle Datei gab kein Array zurück.');
        $this->assertArrayHasKey('test.simple', $data, 'Das Array enthält nicht den erwarteten Test-Schlüssel.');
    }

    /**
     * Testet, ob der Service den Schlüssel selbst zurückgibt,
     * wenn dieser nicht in der Übersetzungsdatei gefunden wird.
     */
    public function testTranslateReturnsKeyIfTranslationMissing()
    {
        $service = $this->createService();
        $missingKey = 'nicht.vorhanden';
        
        $this->assertEquals($missingKey, $service->translate($missingKey));
    }

    /**
     * Testet, ob ein einfacher String (ohne Platzhalter) korrekt geladen wird.
     * Dies validiert den grundlegenden Lademechanismus des Services.
     */
    public function testTranslateReturnsSimpleText()
    {
        $service = $this->createService();
        
        // Erwartet: 'Einfacher Text' (Wert aus dem virtuellen Array)
        // Ist: 'test.simple' (Key), falls das Laden fehlschlägt.
        $this->assertEquals('Einfacher Text', $service->translate('test.simple'));
    }

    /**
     * Testet die Ersetzung eines einzelnen Platzhalters (%name%).
     */
    public function testTranslateReplacesPlaceholders()
    {
        $service = $this->createService();
        
        $result = $service->translate('test.param', ['%name%' => 'Nexus']);
        
        $this->assertEquals('Hallo Nexus', $result);
    }

    /**
     * Testet die Ersetzung mehrerer Platzhalter in einem String.
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
     * Testet die Robustheit des Services.
     * Wenn der Ordner 'translations' fehlt, darf der Service nicht abstürzen (Fatal Error),
     * sondern sollte stabil bleiben (z.B. Keys zurückgeben).
     */
    public function testServiceIsRobustAgainstMissingFile()
    {
        // Wir simulieren ein leeres Root ohne Translations-Ordner
        vfsStream::setup('empty');
        
        $service = new TranslatorService($this->sessionMock, vfsStream::url('empty'));
        
        // Erwartung: Fallback auf den Key
        $this->assertEquals('foo', $service->translate('foo'));
    }
}