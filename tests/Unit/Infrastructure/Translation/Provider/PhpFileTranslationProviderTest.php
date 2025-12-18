<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Translation\Provider;

use MrWo\Nexus\Infrastructure\Translation\Provider\PhpFileTranslationProvider;
use PHPUnit\Framework\TestCase;

/**
 * Testet den Datei-basierten Übersetzungs-Provider.
 * 
 * Validiert:
 * - Laden existierender PHP-Dateien (Array-Return).
 * - Verhalten bei fehlenden Dateien (leeres Array, kein Crash).
 */
class PhpFileTranslationProviderTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/nexus_trans_test_' . uniqid();
        mkdir($this->projectDir . '/translations', 0777, true);
        
        file_put_contents($this->projectDir . '/translations/de.php', "<?php return ['key' => 'Wert'];");
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            array_map('unlink', glob($this->projectDir . '/translations/*'));
            rmdir($this->projectDir . '/translations');
            rmdir($this->projectDir);
        }
    }

    /**
     * Prüft das erfolgreiche Laden einer Sprachdatei.
     */
    public function testLoadTranslationsReturnsArray(): void
    {
        $provider = new PhpFileTranslationProvider($this->projectDir);
        $result = $provider->loadTranslations('de');

        // assertIsArray entfernt, da redundant durch Return-Type
        $this->assertArrayHasKey('key', $result);
        $this->assertEquals('Wert', $result['key']);
    }

    /**
     * Prüft, ob bei fehlender Datei ein leeres Array zurückgegeben wird.
     */
    public function testLoadTranslationsReturnsEmptyOnMissingFile(): void
    {
        $provider = new PhpFileTranslationProvider($this->projectDir);
        $result = $provider->loadTranslations('fr'); // Existiert nicht

        // assertIsArray entfernt
        $this->assertEmpty($result);
    }
}