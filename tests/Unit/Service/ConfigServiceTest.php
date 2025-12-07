<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\ConfigService;
use PHPUnit\Framework\TestCase;

/**
 * Testet den ConfigService.
 * Fokus: Laden von Defaults aus ENV, Lesen/Schreiben der JSON-Datei.
 */
class ConfigServiceTest extends TestCase
{
    private string $projectDir;
    private string $configFile;

    /**
     * Bereitet die Testumgebung vor.
     * Erstellt ein temporäres Verzeichnis und setzt ENV-Variablen.
     */
    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/nexus_config_test_' . uniqid();
        mkdir($this->projectDir . '/config', 0777, true);
        $this->configFile = $this->projectDir . '/config/modules.json';

        // ENV-Variablen für den Test isolieren/setzen
        $_ENV['APP_NAME'] = 'TestApp';
        $_ENV['SESSION_LIFETIME'] = '999';
    }

    /**
     * Räumt nach dem Test auf.
     */
    protected function tearDown(): void
    {
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
        if (is_dir($this->projectDir . '/config')) {
            rmdir($this->projectDir . '/config');
        }
        if (is_dir($this->projectDir)) {
            rmdir($this->projectDir);
        }
        
        // ENV aufräumen
        unset($_ENV['APP_NAME'], $_ENV['SESSION_LIFETIME']);
    }

    /**
     * Prüft, ob Defaults korrekt aus der Umgebung geladen werden,
     * wenn keine Konfigurationsdatei existiert.
     */
    public function testLoadDefaultsFromEnv(): void
    {
        $config = new ConfigService($this->projectDir);

        $this->assertEquals('TestApp', $config->get('app.name'));
        $this->assertEquals(999, $config->get('session.lifetime'));
        
        // Prüfen eines internen Defaults (Fallback)
        $this->assertEquals(true, $config->get('module_cookie_banner'));
    }

    /**
     * Prüft, ob Werte aus einer existierenden Datei die Defaults überschreiben.
     */
    public function testLoadExistingConfigOverridesDefaults(): void
    {
        // Datei anlegen, die den Namen überschreibt
        file_put_contents($this->configFile, json_encode(['app.name' => 'OverriddenApp']));

        $config = new ConfigService($this->projectDir);

        // Datei gewinnt
        $this->assertEquals('OverriddenApp', $config->get('app.name'));
        // ENV bleibt als Fallback für fehlende Keys
        $this->assertEquals(999, $config->get('session.lifetime'));
    }

    /**
     * Prüft das Speichern von Werten.
     */
    public function testSetSavesConfigToFile(): void
    {
        $config = new ConfigService($this->projectDir);
        
        $config->set('new_setting', 'value');

        // Prüfen, ob Datei existiert und Inhalt hat
        $this->assertFileExists($this->configFile);
        $content = json_decode(file_get_contents($this->configFile), true);
        
        $this->assertEquals('value', $content['new_setting']);
    }

    /**
     * Prüft das Verhalten bei defekter JSON-Datei (Fallback auf Defaults).
     */
    public function testGracefulFallbackOnCorruptJson(): void
    {
        file_put_contents($this->configFile, '{invalid_json');

        $config = new ConfigService($this->projectDir);

        // Sollte nicht crashen, sondern Defaults nutzen
        $this->assertEquals('TestApp', $config->get('app.name'));
    }
}