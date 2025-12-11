<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\ConfigService;
use MrWo\Nexus\Repository\ConfigRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Testet den ConfigService.
 * Fokus: Aggregation von Defaults, ENV-Variablen und persistierten Einstellungen.
 * Der Test wird auf den neuen DI-Container-Ansatz umgestellt.
 * @coversDefaultClass \MrWo\Nexus\Service\ConfigService
 */
class ConfigServiceTest extends TestCase
{
    private array $defaultsBeforeTest = [];

    /**
     * Speichert und setzt temporäre ENV-Variablen für den Test.
     */
    protected function setUp(): void
    {
        // Sicherung der globalen Umgebungsvariablen
        $this->defaultsBeforeTest['APP_NAME'] = $_ENV['APP_NAME'] ?? null;
        $this->defaultsBeforeTest['SESSION_LIFETIME'] = $_ENV['SESSION_LIFETIME'] ?? null;

        // ENV-Variablen für den Test isolieren/setzen
        $_ENV['APP_NAME'] = 'TestApp';
        $_ENV['SESSION_LIFETIME'] = '999';
    }

    /**
     * Stellt die ursprünglichen ENV-Variablen wieder her.
     */
    protected function tearDown(): void
    {
        // ENV aufräumen
        if ($this->defaultsBeforeTest['APP_NAME'] === null) {
            unset($_ENV['APP_NAME']);
        } else {
            $_ENV['APP_NAME'] = $this->defaultsBeforeTest['APP_NAME'];
        }
        
        if ($this->defaultsBeforeTest['SESSION_LIFETIME'] === null) {
            unset($_ENV['SESSION_LIFETIME']);
        } else {
            $_ENV['SESSION_LIFETIME'] = $this->defaultsBeforeTest['SESSION_LIFETIME'];
        }
    }

    /**
     * Erstellt eine Instanz des ConfigService mit einem gemockten Repository.
     */
    private function createService(array $persistedSettings = []): ConfigService
    {
        // MOCK: ConfigRepositoryInterface erstellen
        $repositoryMock = $this->createMock(ConfigRepositoryInterface::class);

        // Verhalten: load() gibt persistierte Settings zurück
        $repositoryMock->method('load')->willReturn($persistedSettings);

        return new ConfigService($repositoryMock);
    }
    
    /**
     * Prüft, ob ENV- und System-Defaults korrekt geladen werden,
     * wenn das Repository leer ist.
     * @covers ::__construct
     * @covers ::get
     */
    public function testLoadDefaultsFromEnvAndSystem(): void
    {
        $config = $this->createService([]); // Leeres Repository

        // ENV-Wert
        $this->assertEquals('TestApp', $config->get('app.name'));
        $this->assertEquals(999, $config->get('session.lifetime'));
        
        // Interner Default (Muss in ConfigService::defaults definiert sein)
        $this->assertEquals(true, $config->get('module_cookie_banner'));
        
        // Prüfen eines nicht existierenden Keys mit Default-Rückgabe
        $this->assertEquals('default', $config->get('nonexistent.key', 'default'));
    }

    /**
     * Prüft, ob Werte aus dem Repository die Defaults überschreiben.
     * @covers ::__construct
     * @covers ::get
     */
    public function testLoadExistingConfigOverridesDefaults(): void
    {
        // Simuliere persistierte Einstellung, die app.name überschreibt
        $persisted = [
            'app.name' => 'OverriddenApp',
            'module_site_search' => true // Persistiert
        ];

        $config = $this->createService($persisted);

        // Repository-Wert gewinnt
        $this->assertEquals('OverriddenApp', $config->get('app.name'));
        
        // Interner Default wird überschrieben
        $this->assertTrue($config->get('module_site_search'));

        // ENV bleibt als Fallback für fehlende Keys
        $this->assertEquals(999, $config->get('session.lifetime'));
    }

    /**
     * Prüft das Speichern von Werten.
     * @covers ::set
     * @covers ::get
     */
    public function testSetSavesConfigViaRepository(): void
    {
        // MOCK: ConfigRepositoryInterface erstellen
        $repositoryMock = $this->createMock(ConfigRepositoryInterface::class);
        $repositoryMock->method('load')->willReturn([]);

        // Erwartung: save() wird einmal aufgerufen, mit den neuen Settings
        $repositoryMock->expects($this->once())
             ->method('save')
             ->with(['new_setting' => 'value']);

        $config = new ConfigService($repositoryMock);
        
        $config->set('new_setting', 'value');

        // Prüfen, ob der neue Wert sofort lesbar ist
        $this->assertEquals('value', $config->get('new_setting'));
    }
    
    /**
     * Prüft die getAll Methode.
     * @covers ::getAll
     */
    public function testGetAllReturnsMergedSettings(): void
    {
        // Simuliere persistierte Einstellung
        $persisted = ['module_cookie_banner' => false];

        $config = $this->createService($persisted);

        $allSettings = $config->getAll();
        
        // Prüfen, ob der ENV-Wert dabei ist (aus Defaults)
        $this->assertEquals('TestApp', $allSettings['app.name']);

        // Prüfen, ob die persistierte Einstellung die Defaults überschreibt
        // Default ist TRUE, persistiert ist FALSE
        $this->assertFalse($allSettings['module_cookie_banner']);
    }

    /**
     * Prüft die isEnabled Methode.
     * @covers ::isEnabled
     */
    public function testIsEnabledReturnsCorrectBoolean(): void
    {
        $persisted = [
            'is_true' => true,
            'is_false' => false,
            'is_string_true' => '1',
            'is_null' => null
        ];
        $config = $this->createService($persisted);

        $this->assertTrue($config->isEnabled('is_true'));
        $this->assertFalse($config->isEnabled('is_false'));
        $this->assertTrue($config->isEnabled('is_string_true'));
        $this->assertFalse($config->isEnabled('is_null'));
        
        // Nicht existierend sollte false zurückgeben
        $this->assertFalse($config->isEnabled('nonexistent'));
    }
}