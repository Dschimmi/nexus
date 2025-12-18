<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Twig;

use MrWo\Nexus\Twig\AppExtension;
use MrWo\Nexus\Infrastructure\Translation\TranslatorService;
use MrWo\Nexus\Infrastructure\Asset\AssetService;
use MrWo\Nexus\Infrastructure\Config\ConfigService;
use MrWo\Nexus\Infrastructure\Session\SessionService;
use MrWo\Nexus\Application\Page\PageManager;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Testet die Twig-Extension AppExtension.
 * 
 * Validiert, dass:
 * - Die erwarteten Filter ('trans') registriert werden.
 * - Die erwarteten Funktionen ('asset', 'config', etc.) registriert werden.
 * - Die Funktionen ihre Aufrufe korrekt an die zugrundeliegenden Services delegieren.
 */
class AppExtensionTest extends TestCase
{
    private $translatorMock;
    private $assetMock;
    private $configMock;
    private $sessionMock;
    private $pageManagerMock;
    private $extension;

    /**
     * Initialisiert die Mocks und die Extension vor jedem Test.
     */
    protected function setUp(): void
    {
        $this->translatorMock = $this->createMock(TranslatorService::class);
        $this->assetMock = $this->createMock(AssetService::class);
        $this->configMock = $this->createMock(ConfigService::class);
        $this->sessionMock = $this->createMock(SessionService::class);
        $this->pageManagerMock = $this->createMock(PageManager::class);

        $this->extension = new AppExtension(
            $this->translatorMock,
            $this->assetMock,
            $this->configMock,
            $this->sessionMock,
            $this->pageManagerMock
        );
    }

    /**
     * Prüft, ob der 'trans' Filter korrekt registriert ist.
     */
    public function testGetFiltersReturnsTransFilter(): void
    {
        $filters = $this->extension->getFilters();
        
        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('trans', $filters[0]->getName());
    }

    /**
     * Prüft, ob die 'asset' Funktion an den AssetService delegiert.
     */
    public function testAssetFunctionDelegatesToService(): void
    {
        // Expect: AssetService->get('style.css')
        $this->assetMock->expects($this->once())
            ->method('get')
            ->with('style.css')
            ->willReturn('/build/style.css');

        $functions = $this->getFunctionsMap();
        
        // Callback ausführen
        $callback = $functions['asset']->getCallable();
        $result = $callback('style.css');
        
        $this->assertEquals('/build/style.css', $result);
    }

    /**
     * Prüft, ob die 'config' Funktion an den ConfigService delegiert.
     */
    public function testConfigFunctionDelegatesToService(): void
    {
        // Expect: ConfigService->isEnabled('feature_x')
        $this->configMock->expects($this->once())
            ->method('isEnabled')
            ->with('feature_x')
            ->willReturn(true);

        $functions = $this->getFunctionsMap();
        
        $callback = $functions['config']->getCallable();
        $this->assertTrue($callback('feature_x'));
    }

    /**
     * Prüft, ob die 'get_dummy_pages' Funktion an den PageManager delegiert.
     */
    public function testGetDummyPagesFunctionDelegatesToService(): void
    {
        // Expect: PageManager->getPages()
        $this->pageManagerMock->expects($this->once())
            ->method('getPages')
            ->willReturn(['page1', 'page2']);

        $functions = $this->getFunctionsMap();
        
        $callback = $functions['get_dummy_pages']->getCallable();
        $this->assertEquals(['page1', 'page2'], $callback());
    }

    /**
     * Hilfsmethode, um Twig-Funktionen als Map (Name => FunctionObject) zu bekommen.
     * Erleichtert den Zugriff auf spezifische Funktionen im Test.
     */
    private function getFunctionsMap(): array
    {
        $functions = $this->extension->getFunctions();
        $map = [];
        foreach ($functions as $fn) {
            $map[$fn->getName()] = $fn;
        }
        return $map;
    }
}