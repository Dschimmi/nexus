<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\ConfigService;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ConfigServiceTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        // Virtuelles Dateisystem erstellen
        vfsStream::setup('root');
        $this->projectDir = vfsStream::url('root');

        // Den 'config' Ordner erstellen, da der Service diesen erwartet
        mkdir($this->projectDir . '/config');
    }

    public function testLoadDefaultsIfFileDoesNotExist()
    {
        // Wir erstellen KEINE modules.json
        $service = new ConfigService($this->projectDir);

        // Prüfung auf Standardwerte (siehe ConfigService.php $defaults)
        // Cookie Banner sollte standardmäßig an sein
        $this->assertTrue($service->isEnabled('module_cookie_banner'), 'Default für Cookie-Banner sollte TRUE sein.');
        
        // User Management sollte standardmäßig aus sein
        $this->assertFalse($service->isEnabled('module_user_management'), 'Default für User-Management sollte FALSE sein.');
    }

    public function testLoadExistingConfig()
    {
        // Wir legen eine Konfigurationsdatei an, die vom Standard abweicht
        $configData = [
            'module_user_management' => true,
            'module_cookie_banner' => false
        ];
        
        file_put_contents(
            $this->projectDir . '/config/modules.json',
            json_encode($configData)
        );

        $service = new ConfigService($this->projectDir);

        // Prüfung: Wurden die Werte aus der Datei übernommen?
        $this->assertTrue($service->isEnabled('module_user_management'), 'User-Management sollte durch Datei aktiviert sein.');
        $this->assertFalse($service->isEnabled('module_cookie_banner'), 'Cookie-Banner sollte durch Datei deaktiviert sein.');
    }

    public function testSetSavesConfigToFile()
    {
        $service = new ConfigService($this->projectDir);

        // Wir ändern eine Einstellung
        $service->set('module_site_search', true);

        // Prüfung 1: Ist der Wert im Service gesetzt?
        $this->assertTrue($service->isEnabled('module_site_search'));

        // Prüfung 2: Wurde die Datei geschrieben?
        $filePath = $this->projectDir . '/config/modules.json';
        $this->assertFileExists($filePath);

        // Prüfung 3: Steht der korrekte Inhalt drin?
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        $this->assertIsArray($data);
        $this->assertTrue($data['module_site_search']);
    }

    public function testGracefulFallbackOnCorruptJson()
    {
        // Wir erstellen eine defekte JSON-Datei
        file_put_contents(
            $this->projectDir . '/config/modules.json',
            '{ "broken": "json", ,,, }' 
        );

        // Der Service darf hier nicht abstürzen, sondern sollte die Defaults nutzen
        $service = new ConfigService($this->projectDir);

        // Prüfung: Fallback auf Defaults
        $this->assertTrue($service->isEnabled('module_cookie_banner'), 'Sollte bei defektem JSON auf Default zurückfallen.');
    }
}