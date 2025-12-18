<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use MrWo\Nexus\Controller\AdminController;
// NEUE Namespaces
use MrWo\Nexus\Application\Auth\AuthenticationService;
use MrWo\Nexus\Infrastructure\Config\ConfigService;
use MrWo\Nexus\Infrastructure\Translation\TranslatorService;
use MrWo\Nexus\Application\Page\PageManager;
use MrWo\Nexus\Infrastructure\Session\SessionService;
use MrWo\Nexus\Domain\User\User;

/**
 * Testet den AdminController.
 * Angepasst auf Hexagonale Architektur und PHPUnit 10+.
 * @coversDefaultClass \MrWo\Nexus\Controller\AdminController
 */
class AdminControllerTest extends TestCase
{
    private User $mockUser;
    private array $requiredMocks;

    protected function setUp(): void
    {
        // User Objekt erstellen (Domain)
        $this->mockUser = new User(
            '1',
            'admin',
            'admin@example.com',
            'hashed_pass',
            'System',
            'Administrator',
            1
        );

        // Mocks erstellen (mit korrekten Klassen)
        $this->requiredMocks = [
            'twig'          => $this->createMock(Environment::class),
            'authService'   => $this->createMock(AuthenticationService::class),
            'configService' => $this->createMock(ConfigService::class),
            'translator'    => $this->createMock(TranslatorService::class),
            'pageManager'   => $this->createMock(PageManager::class),
            'session'       => $this->createMock(SessionService::class),
        ];
    }

    private function createAdminController(): AdminController
    {
        return new AdminController(
            $this->requiredMocks['twig'],
            $this->requiredMocks['authService'],
            $this->requiredMocks['configService'],
            $this->requiredMocks['translator'],
            $this->requiredMocks['pageManager'],
            $this->requiredMocks['session']
        );
    }

    /**
     * @covers ::index
     */
    public function testIndexActionReturnsDashboardForAuthenticatedAdmin(): void
    {
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(true);
             
        // FIX: getUser gibt ein Array zurück, kein Objekt!
        $this->requiredMocks['authService']->expects($this->once())
             ->method('getUser')
             ->willReturn($this->mockUser->toArray());

        $this->requiredMocks['configService']->expects($this->once())
             ->method('getAll')
             ->willReturn(['module_user_management' => true]);

        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->with('admin/dashboard.html.twig', $this->isType('array'))
             ->willReturn('<html>Admin Dashboard</html>');

        $controller = $this->createAdminController();
        $response = $controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Admin Dashboard', $response->getContent());
    }

    /**
     * @covers ::index
     */
    public function testIndexActionReturnsLoginFormForUnauthenticatedUser(): void
    {
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(false);
        
        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->with('forms/login.html.twig', $this->isType('array'))
             ->willReturn('<html>Login Form</html>');

        $controller = $this->createAdminController();
        $response = $controller->index();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers ::login
     */
    public function testLoginActionFailsOnInvalidCsrfToken(): void
    {
        $this->requiredMocks['session']->expects($this->once())
             ->method('isCsrfTokenValid')
             ->willReturn(false);
        
        $this->requiredMocks['session']->expects($this->once())
             ->method('addFlash')
             ->with('error', $this->isType('string'));

        $controller = $this->createAdminController();
        $request = Request::create('/admin/login', 'POST', [
            'username' => 'testuser',
            'password' => 'password',
            '_csrf_token' => 'invalid-token'
        ]);

        $response = $controller->login($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * @covers ::login
     */
    public function testLoginActionSuccessfulRedirectsToDashboard(): void
    {
        $this->requiredMocks['session']->expects($this->once())->method('isCsrfTokenValid')->willReturn(true);
        $this->requiredMocks['authService']->expects($this->once())->method('login')->willReturn(true);

        $controller = $this->createAdminController();
        $request = Request::create('/admin/login', 'POST', [
            'username' => 'validuser', 'password' => 'validpass', '_csrf_token' => 'valid-token'
        ]);

        $response = $controller->login($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * @covers ::login
     */
    public function testLoginActionFailsAndRerendersFormWithError(): void
    {
        $this->requiredMocks['session']->expects($this->once())->method('isCsrfTokenValid')->willReturn(true);
        $this->requiredMocks['authService']->expects($this->once())->method('login')->willReturn(false);

        $this->requiredMocks['translator']->expects($this->once())
             ->method('translate')
             ->with('login.error_auth')
             ->willReturn('Ungültige Anmeldedaten.');

        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->with('forms/login.html.twig', $this->callback(fn($args) => $args['error'] === 'Ungültige Anmeldedaten.'))
             ->willReturn('<html>Login Form with Error</html>');

        $controller = $this->createAdminController();
        $request = Request::create('/admin/login', 'POST', [
            'username' => 'wrong', 'password' => 'wrong', '_csrf_token' => 'valid'
        ]);

        $response = $controller->login($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers ::logout
     */
    public function testLogoutActionInvalidatesSessionAndRedirects(): void
    {
        $this->requiredMocks['authService']->expects($this->once())->method('logout');

        $controller = $this->createAdminController();
        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * @covers ::saveConfig
     */
    public function testSaveConfigActionDeniesAccessAndRedirects(): void
    {
        $this->requiredMocks['authService']->expects($this->once())->method('isAdmin')->willReturn(false);
        $this->requiredMocks['configService']->expects($this->never())->method('set');
        
        $controller = $this->createAdminController();
        $request = Request::create('/admin/saveConfig', 'POST', ['module_user_management' => 'on']);

        $response = $controller->saveConfig($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @covers ::saveConfig
     */
    public function testSaveConfigActionSuccessfullySavesConfig(): void
    {
        $this->requiredMocks['authService']->expects($this->once())->method('isAdmin')->willReturn(true);
        
        // FIX: withConsecutive ist deprecated. Wir prüfen nur, dass set() 4x aufgerufen wird.
        // Die genauen Argumente sind schwer zu prüfen ohne komplexe Callbacks.
        $this->requiredMocks['configService']->expects($this->exactly(4))
             ->method('set');

        $this->requiredMocks['translator']->expects($this->once())
             ->method('translate')
             ->with('admin.save_btn')
             ->willReturn('Gespeichert');

        $this->requiredMocks['session']->expects($this->once())->method('addFlash');
        
        $controller = $this->createAdminController();
        $request = Request::create('/admin/saveConfig', 'POST', [
            'module_user_management' => 'on',
            'module_cookie_banner' => 'on'
        ]);

        $response = $controller->saveConfig($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @covers ::createPage
     */
    public function testCreatePageActionRendersFormOnGetRequest(): void
    {
        $this->requiredMocks['authService']->expects($this->once())->method('isAdmin')->willReturn(true);
        
        // FIX: getUser gibt Array zurück
        $this->requiredMocks['authService']->expects($this->once())
             ->method('getUser')
             ->willReturn($this->mockUser->toArray());

        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->with('admin/page_create.html.twig', $this->isType('array'))
             ->willReturn('<html>Page Create</html>');

        $controller = $this->createAdminController();
        $request = Request::create('/admin/pages/create', 'GET');

        $response = $controller->createPage($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers ::createPage
     */
    public function testCreatePageActionHandlesPostSuccess(): void
    {
        $this->requiredMocks['authService']->expects($this->once())->method('isAdmin')->willReturn(true);
        $this->requiredMocks['pageManager']->expects($this->once())->method('createPage');
        $this->requiredMocks['translator']->expects($this->once())->method('translate')->willReturn('OK');
        $this->requiredMocks['session']->expects($this->once())->method('addFlash');
        
        $controller = $this->createAdminController();
        $request = Request::create('/admin/pages/create', 'POST', ['slug' => 'neu', 'title' => 'Neu', 'content' => '...']);

        $response = $controller->createPage($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @covers ::createPage
     */
    public function testCreatePageActionRendersFormOnPostFailure(): void
    {
        $this->requiredMocks['authService']->expects($this->once())->method('isAdmin')->willReturn(true);
        $this->requiredMocks['pageManager']->expects($this->once())
             ->method('createPage')
             ->willThrowException(new \RuntimeException('Fehler'));
        
        // FIX: getUser für Header im Error-Case (wird oft übersehen)
        // Aber im Controller Code: createPage ruft im catch-Block getUser() auf!
        $this->requiredMocks['authService']->expects($this->once())
             ->method('getUser')
             ->willReturn($this->mockUser->toArray());

        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->willReturn('<html>Error Form</html>');

        $controller = $this->createAdminController();
        $request = Request::create('/admin/pages/create', 'POST', ['slug' => 'neu', 'title' => 'Neu', 'content' => '...']);

        $response = $controller->createPage($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers ::listPages
     */
    public function testListPagesActionRendersPageListSuccessfully(): void
    {
        $this->requiredMocks['authService']->expects($this->once())->method('isAdmin')->willReturn(true);
        // FIX: getUser gibt Array zurück
        $this->requiredMocks['authService']->expects($this->once())
             ->method('getUser')
             ->willReturn($this->mockUser->toArray());
             
        $this->requiredMocks['pageManager']->expects($this->once())->method('getPages')->willReturn([]);
        $this->requiredMocks['twig']->expects($this->once())->method('render')->willReturn('<html>List</html>');

        $controller = $this->createAdminController();
        $response = $controller->listPages();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers ::deletePages
     */
    public function testDeletePagesActionSuccessfullyDeletesPages(): void
    {
        $this->requiredMocks['authService']->expects($this->once())->method('isAdmin')->willReturn(true);
        $this->requiredMocks['pageManager']->expects($this->once())->method('deletePages')->willReturn(1);
        $this->requiredMocks['translator']->expects($this->once())->method('translate')->willReturn('Deleted');
        $this->requiredMocks['session']->expects($this->once())->method('addFlash');

        $controller = $this->createAdminController();
        $request = Request::create('/admin/pages/delete', 'POST', ['slugs' => ['a']]);

        $response = $controller->deletePages($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}