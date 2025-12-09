<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Service;

use MrWo\Nexus\Service\TranslatorService;
use MrWo\Nexus\Service\Provider\PhpFileTranslationProvider; // Neu
use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\SessionBag;
use PHPUnit\Framework\TestCase;

class TranslatorServiceTest extends TestCase
{
    private $sessionMock;
    private $attributeBagMock;
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/nexus_test_' . uniqid();
        mkdir($this->projectDir . '/translations', 0777, true);

        // Dummy-Dateien
        file_put_contents($this->projectDir . '/translations/de.php', "<?php return ['welcome' => 'Willkommen', 'bye' => 'Tschüss', 'hello' => 'Hallo %name%'];");
        file_put_contents($this->projectDir . '/translations/en.php', "<?php return ['welcome' => 'Welcome', 'bye' => 'Bye'];");

        // Mocking
        $this->sessionMock = $this->createMock(SessionService::class);
        $this->attributeBagMock = $this->createMock(SessionBag::class);

        $this->sessionMock->method('getBag')
            ->with('attributes')
            ->willReturn($this->attributeBagMock);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            array_map('unlink', glob($this->projectDir . '/translations/*'));
            rmdir($this->projectDir . '/translations');
            rmdir($this->projectDir);
        }
    }

    public function testDefaultLocaleIsDe(): void
    {
        // Session leer -> Default
        $this->attributeBagMock->method('has')->willReturn(false);

        // Neuer Konstruktor (nur Session)
        $translator = new TranslatorService($this->sessionMock);
        
        // Provider hinzufügen
        $provider = new PhpFileTranslationProvider($this->projectDir);
        $translator->addProvider($provider);

        $this->assertEquals('de', $translator->getLocale());
        $this->assertEquals('Willkommen', $translator->translate('welcome'));
    }

    public function testLocaleFromSession(): void
    {
        $this->attributeBagMock->method('has')->with('locale')->willReturn(true);
        $this->attributeBagMock->method('get')->with('locale')->willReturn('en');

        $translator = new TranslatorService($this->sessionMock);
        $translator->addProvider(new PhpFileTranslationProvider($this->projectDir));

        $this->assertEquals('en', $translator->getLocale());
        $this->assertEquals('Welcome', $translator->translate('welcome'));
    }

    public function testTranslateWithParams(): void
    {
        $this->attributeBagMock->method('has')->willReturn(false); // DE

        $translator = new TranslatorService($this->sessionMock);
        $translator->addProvider(new PhpFileTranslationProvider($this->projectDir));

        $this->assertEquals('Hallo Max', $translator->translate('hello', ['%name%' => 'Max']));
    }
}