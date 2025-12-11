<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use MrWo\Nexus\Controller\AdminController;
use MrWo\Nexus\Service\AuthenticationService;
use MrWo\Nexus\Service\ConfigService;
use MrWo\Nexus\Service\TranslatorService;
use MrWo\Nexus\Service\PageManagerService;
use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Entity\User;

/**
 * Testet den AdminController (Index-Methode).
 * (Ticket 0000074: Keine Controller-Tests)
 * @coversDefaultClass \MrWo\Nexus\Controller\AdminController
 */
class AdminControllerTest extends TestCase
{
    private User $mockUser;
    private array $requiredMocks;

    protected function setUp(): void
    {
        // KORRIGIERTE INSTANZIIERUNG gemäß src/Entity/User.php Konstruktor:
        // Argumente: ID (string), Username (string), Email (string), Hash (string), Group (string), Role (string), AuthVersion (int)
        $this->mockUser = new User(
            '1',                     // ID (string)
            'admin',                 // Username
            'admin@example.com',     // Email
            'hashed_pass',           // PasswordHash
            'System',                // Group (string)
            'Administrator'          // Role (string)
            // AuthVersion (int) ist optional und wird auf 1 gesetzt
        );

        // Erstellen aller notwendigen Mocks für den AdminController-Konstruktor
        $this->requiredMocks = [
            'twig'          => $this->createMock(Environment::class),
            'authService'   => $this->createMock(AuthenticationService::class),
            'configService' => $this->createMock(ConfigService::class),
            'translator'    => $this->createMock(TranslatorService::class),
            'pageManager'   => $this->createMock(PageManagerService::class),
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
     * Testet die Index-Aktion bei **erfolgreicher** Authentifizierung (Dashboard).
     * @covers ::index
     */
    public function testIndexActionReturnsDashboardForAuthenticatedAdmin(): void
    {
        // Verhalten: User ist Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(true);
        $this->requiredMocks['authService']->expects($this->once())
             ->method('getUser')
             ->willReturn($this->mockUser);

        // Verhalten: ConfigService liefert Modul-Daten
        $this->requiredMocks['configService']->expects($this->once())
             ->method('getAll')
             ->willReturn(['module_user_management' => true]);

        // Verhalten: Twig rendert Dashboard
        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->with('admin/dashboard.html.twig', $this->isType('array'))
             ->willReturn('<html>Admin Dashboard</html>');

        $controller = $this->createAdminController();
        $response = $controller->index();

        // Assertions
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Admin Dashboard', $response->getContent());
    }

    /**
     * Testet die Index-Aktion bei **fehlender** Authentifizierung (Login-Formular).
     * @covers ::index
     */
    public function testIndexActionReturnsLoginFormForUnauthenticatedUser(): void
    {
        // Verhalten: User ist kein Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(false);
        
        // Verhalten: Twig rendert Login-Formular
        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->with('forms/login.html.twig', $this->isType('array'))
             ->willReturn('<html>Login Form</html>');

        $controller = $this->createAdminController();
        $response = $controller->index();

        // Assertions
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Login Form', $response->getContent());
    }

    /**
     * Testet die Login-Aktion bei fehlgeschlagenem CSRF-Token.
     * Erwartet: RedirectResponse zur Admin-Seite.
     * @covers ::login
     */
    public function testLoginActionFailsOnInvalidCsrfToken(): void
    {
        // Verhalten: SessionService meldet ungültigen Token
        $this->requiredMocks['session']->expects($this->once())
             ->method('isCsrfTokenValid')
             ->willReturn(false);
        
        // Verhalten: Flash-Message wird gesetzt
        $this->requiredMocks['session']->expects($this->once())
             ->method('addFlash')
             ->with('error', $this->isType('string'));

        $controller = $this->createAdminController();
        
        // Request mit Dummy-Token erstellen
        $request = Request::create('/admin/login', 'POST', [
            'username' => 'testuser',
            'password' => 'password',
            '_csrf_token' => 'invalid-token'
        ]);

        $response = $controller->login($request);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * Testet die Login-Aktion bei erfolgreicher Authentifizierung.
     * Erwartet: RedirectResponse zum Dashboard.
     * @covers ::login
     */
    public function testLoginActionSuccessfulRedirectsToDashboard(): void
    {
        // Verhalten: SessionService meldet gültigen Token
        $this->requiredMocks['session']->expects($this->once())
             ->method('isCsrfTokenValid')
             ->willReturn(true);
        
        // Verhalten: AuthenticationService meldet erfolgreichen Login
        $this->requiredMocks['authService']->expects($this->once())
             ->method('login')
             ->willReturn(true);

        $controller = $this->createAdminController();
        
        $request = Request::create('/admin/login', 'POST', [
            'username' => 'validuser',
            'password' => 'validpass',
            '_csrf_token' => 'valid-token'
        ]);

        $response = $controller->login($request);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * Testet die Login-Aktion bei fehlgeschlagenen Credentials.
     * Erwartet: Response mit erneut gerendertem Formular und Fehlermeldung.
     * @covers ::login
     */
    public function testLoginActionFailsAndRerendersFormWithError(): void
    {
        // Verhalten: SessionService meldet gültigen Token
        $this->requiredMocks['session']->expects($this->once())
             ->method('isCsrfTokenValid')
             ->willReturn(true);
        
        // Verhalten: AuthenticationService meldet fehlgeschlagenen Login
        $this->requiredMocks['authService']->expects($this->once())
             ->method('login')
             ->willReturn(false);

        // Verhalten: TranslatorService liefert Fehlermeldung
        $this->requiredMocks['translator']->expects($this->once())
             ->method('translate')
             ->with('login.error_auth')
             ->willReturn('Ungültige Anmeldedaten.');

        // Verhalten: Twig rendert Login-Formular mit Fehler
        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->with('forms/login.html.twig', $this->callback(function ($args) {
                 // Prüft, ob der Fehler korrekt im Render-Array enthalten ist
                 return isset($args['error']) && $args['error'] === 'Ungültige Anmeldedaten.';
             }))
             ->willReturn('<html>Login Form with Error</html>');

        $controller = $this->createAdminController();
        
        $request = Request::create('/admin/login', 'POST', [
            'username' => 'wronguser',
            'password' => 'wrongpass',
            '_csrf_token' => 'valid-token'
        ]);

        $response = $controller->login($request);

        // Assertions
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Login Form with Error', $response->getContent());
    }
    /**
     * Testet die Logout-Aktion.
     * Erwartet: Aufruf von authService->logout() und RedirectResponse zur Admin-Seite.
     * @covers ::logout
     */
    public function testLogoutActionInvalidatesSessionAndRedirects(): void
    {
        // Verhalten: AuthenticationService->logout() muss aufgerufen werden
        $this->requiredMocks['authService']->expects($this->once())
             ->method('logout');

        $controller = $this->createAdminController();
        
        $response = $controller->logout();

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * Testet die saveConfig-Aktion bei fehlender Authentifizierung.
     * Erwartet: Direkter Redirect zur Admin-Seite.
     * @covers ::saveConfig
     */
    public function testSaveConfigActionDeniesAccessAndRedirects(): void
    {
        // Verhalten: User ist NICHT Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(false);

        // Verhalten: KEIN Aufruf von ConfigService->set() oder SessionService->addFlash()
        $this->requiredMocks['configService']->expects($this->never())
             ->method('set');
        $this->requiredMocks['session']->expects($this->never())
             ->method('addFlash');
        
        $controller = $this->createAdminController();
        
        // Request mit Dummy-Daten erstellen
        $request = Request::create('/admin/saveConfig', 'POST', [
            'module_user_management' => 'on'
        ]);

        $response = $controller->saveConfig($request);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * Testet die saveConfig-Aktion bei erfolgreicher Authentifizierung.
     * Erwartet: Aktualisierung der Config, Erfolgsmeldung und Redirect.
     * @covers ::saveConfig
     */
    public function testSaveConfigActionSuccessfullySavesConfig(): void
    {
        // Verhalten: User ist Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(true);
        
        // Verhalten: ConfigService->set() muss für alle vier Module aufgerufen werden.
        $this->requiredMocks['configService']->expects($this->exactly(4))
             ->method('set')
             ->withConsecutive(
                 ['module_user_management', true],  // 'on' -> true
                 ['module_site_search', false],     // fehlt im Request -> false
                 ['module_cookie_banner', true],   // 'on' -> true
                 ['module_language_selection', false] // fehlt im Request -> false
             );

        // Verhalten: TranslatorService liefert Text
        $this->requiredMocks['translator']->expects($this->once())
             ->method('translate')
             ->with('admin.save_btn')
             ->willReturn('Konfiguration gespeichert');

        // Verhalten: Erfolgsmeldung wird gesetzt
        $this->requiredMocks['session']->expects($this->once())
             ->method('addFlash')
             ->with('success', 'Konfiguration gespeichert OK');
        
        $controller = $this->createAdminController();
        
        // Request mit aktivierten Modulen 1 und 3 erstellen
        $request = Request::create('/admin/saveConfig', 'POST', [
            'module_user_management' => 'on',
            // module_site_search fehlt
            'module_cookie_banner' => 'on',
            // module_language_selection fehlt
        ]);

        $response = $controller->saveConfig($request);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }
    /**
     * Testet die createPage-Aktion bei fehlender Authentifizierung (GET oder POST).
     * Erwartet: Direkter Redirect zur Admin-Seite.
     * @covers ::createPage
     */
    public function testCreatePageActionDeniesAccessAndRedirects(): void
    {
        // Verhalten: User ist NICHT Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(false);

        // Verhalten: KEIN Aufruf von Twig oder PageManager
        $this->requiredMocks['twig']->expects($this->never())
             ->method('render');
        
        $controller = $this->createAdminController();
        
        // Testen mit einem GET-Request (POST würde dasselbe Ergebnis liefern)
        $request = Request::create('/admin/pages/create', 'GET');

        $response = $controller->createPage($request);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * Testet die createPage-Aktion bei einem GET-Request (Formular anzeigen).
     * Erwartet: Erfolgreicher 200 Response mit dem Erstellungs-Formular.
     * @covers ::createPage
     */
    public function testCreatePageActionRendersFormOnGetRequest(): void
    {
        // Verhalten: User ist Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(true);
        $this->requiredMocks['authService']->expects($this->once())
             ->method('getUser')
             ->willReturn($this->mockUser);

        // Verhalten: Twig rendert das Erstellungs-Formular
        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->with('admin/page_create.html.twig', $this->callback(function ($args) {
                 return isset($args['user']); // Prüft, ob der User für den Header übergeben wird
             }))
             ->willReturn('<html>Page Create Form</html>');

        $controller = $this->createAdminController();
        
        $request = Request::create('/admin/pages/create', 'GET');

        $response = $controller->createPage($request);

        // Assertions
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Page Create Form', $response->getContent());
    }

    /**
     * Testet die createPage-Aktion bei erfolgreichem POST-Request.
     * Erwartet: Aufruf von PageManager->createPage, Flash-Message und Redirect.
     * @covers ::createPage
     */
    public function testCreatePageActionHandlesPostSuccess(): void
    {
        // Verhalten: User ist Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(true);
        
        // Verhalten: PageManager->createPage wird aufgerufen
        $this->requiredMocks['pageManager']->expects($this->once())
             ->method('createPage')
             ->with('neue-seite', 'Neue Seite', 'Content');

        // Verhalten: TranslatorService liefert Text
        $this->requiredMocks['translator']->expects($this->once())
             ->method('translate')
             ->with('admin.page_success')
             ->willReturn('Seite erfolgreich erstellt.');

        // Verhalten: Erfolgsmeldung wird gesetzt
        $this->requiredMocks['session']->expects($this->once())
             ->method('addFlash')
             ->with('success', 'Seite erfolgreich erstellt.');
        
        $controller = $this->createAdminController();
        
        // Request mit gültigen Daten erstellen
        $request = Request::create('/admin/pages/create', 'POST', [
            'slug'    => 'neue-seite',
            'title'   => 'Neue Seite',
            'content' => 'Content'
        ]);

        $response = $controller->createPage($request);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * Testet die createPage-Aktion bei POST-Fehler (RuntimeException).
     * Erwartet: Response mit erneut gerendertem Formular und Fehlermeldung.
     * @covers ::createPage
     */
    public function testCreatePageActionRendersFormOnPostFailure(): void
    {
        // Verhalten: User ist Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(true);
        
        // Verhalten: PageManager->createPage wirft RuntimeException
        $this->requiredMocks['pageManager']->expects($this->once())
             ->method('createPage')
             ->willThrowException(new \RuntimeException('Slug existiert bereits.'));

        // Verhalten: Twig rendert Formular mit Fehler und Last-Daten
        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->with('admin/page_create.html.twig', $this->callback(function ($args) {
                 // Prüft, ob der Fehler und die alten Daten übergeben wurden
                 return isset($args['error']) && $args['error'] === 'Slug existiert bereits.' &&
                        $args['last_slug'] === 'duplikat';
             }))
             ->willReturn('<html>Page Create Form with Error</html>');

        $controller = $this->createAdminController();
        
        // Request mit Dummy-Daten erstellen
        $request = Request::create('/admin/pages/create', 'POST', [
            'slug'    => 'duplikat',
            'title'   => 'Duplikat',
            'content' => 'Content'
        ]);

        $response = $controller->createPage($request);

        // Assertions
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Page Create Form with Error', $response->getContent());
    }

    /**
     * Testet die listPages-Aktion bei fehlender Authentifizierung.
     * Erwartet: Direkter Redirect zur Admin-Seite.
     * @covers ::listPages
     */
    public function testListPagesActionDeniesAccessAndRedirects(): void
    {
        // Verhalten: User ist NICHT Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(false);

        // Verhalten: KEIN Aufruf von Twig oder PageManager
        $this->requiredMocks['twig']->expects($this->never())
             ->method('render');
        
        $controller = $this->createAdminController();
        
        // Request simulieren (wird hier ignoriert)
        $response = $controller->listPages();

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * Testet die listPages-Aktion bei erfolgreicher Authentifizierung.
     * Erwartet: Erfolgreicher 200 Response mit der Seitenliste.
     * @covers ::listPages
     */
    public function testListPagesActionRendersPageListSuccessfully(): void
    {
        $mockPages = [['slug' => 'test-seite', 'title' => 'Test Seite']];
        
        // Verhalten: User ist Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(true);
        $this->requiredMocks['authService']->expects($this->once())
             ->method('getUser')
             ->willReturn($this->mockUser);

        // Verhalten: PageManager liefert Seiten
        $this->requiredMocks['pageManager']->expects($this->once())
             ->method('getPages')
             ->willReturn($mockPages);

        // Verhalten: Twig rendert die Seitenliste
        $this->requiredMocks['twig']->expects($this->once())
             ->method('render')
             ->with('admin/pages_list.html.twig', $this->callback(function ($args) use ($mockPages) {
                 return isset($args['pages']) && $args['pages'] === $mockPages;
             }))
             ->willReturn('<html>Page List Content</html>');

        $controller = $this->createAdminController();
        
        $response = $controller->listPages();

        // Assertions
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Page List Content', $response->getContent());
    }

    /**
     * Testet die deletePages-Aktion bei fehlender Authentifizierung.
     * Erwartet: Direkter Redirect zur Admin-Seite.
     * @covers ::deletePages
     */
    public function testDeletePagesActionDeniesAccessAndRedirects(): void
    {
        // Verhalten: User ist NICHT Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(false);

        // Verhalten: KEIN Aufruf von PageManager
        $this->requiredMocks['pageManager']->expects($this->never())
             ->method('deletePages');
        
        $controller = $this->createAdminController();
        
        // Request simulieren
        $request = Request::create('/admin/pages/delete', 'POST', ['slugs' => ['test']]);

        $response = $controller->deletePages($request);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * Testet die deletePages-Aktion bei erfolgreicher Löschung.
     * Erwartet: Aufruf von deletePages, Erfolgsmeldung und Redirect.
     * @covers ::deletePages
     */
    public function testDeletePagesActionSuccessfullyDeletesPages(): void
    {
        $slugsToDelete = ['seite-a', 'seite-b'];

        // Verhalten: User ist Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(true);

        // Verhalten: PageManager->deletePages wird aufgerufen und meldet 2 gelöschte Seiten
        $this->requiredMocks['pageManager']->expects($this->once())
             ->method('deletePages')
             ->with($slugsToDelete)
             ->willReturn(2);

        // Verhalten: TranslatorService liefert Text
        $this->requiredMocks['translator']->expects($this->once())
             ->method('translate')
             ->with('admin.pages_delete_ok')
             ->willReturn('Seiten erfolgreich gelöscht');

        // Verhalten: Erfolgsmeldung wird gesetzt
        $this->requiredMocks['session']->expects($this->once())
             ->method('addFlash')
             ->with('success', 'Seiten erfolgreich gelöscht (2)');
        
        $controller = $this->createAdminController();
        
        // Request mit Seiten-Slugs erstellen
        $request = Request::create('/admin/pages/delete', 'POST', [
            'slugs' => $slugsToDelete
        ]);

        $response = $controller->deletePages($request);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin/pages', $response->getTargetUrl());
    }
    
    /**
     * Testet die deletePages-Aktion, wenn keine Seiten ausgewählt wurden.
     * Erwartet: Info-Flash-Message und Redirect.
     * @covers ::deletePages
     */
    public function testDeletePagesActionShowsInfoIfNoSlugsSelected(): void
    {
        // Verhalten: User ist Admin
        $this->requiredMocks['authService']->expects($this->once())
             ->method('isAdmin')
             ->willReturn(true);

        // Verhalten: PageManager->deletePages wird NICHT aufgerufen
        $this->requiredMocks['pageManager']->expects($this->never())
             ->method('deletePages');

        // Verhalten: Info-Flash-Message wird gesetzt
        $this->requiredMocks['session']->expects($this->once())
             ->method('addFlash')
             ->with('info', 'Keine Seiten ausgewählt.');
        
        $controller = $this->createAdminController();
        
        // Request ohne 'slugs' oder mit leerem Array erstellen
        $request = Request::create('/admin/pages/delete', 'POST', [
            'slugs' => []
        ]);

        $response = $controller->deletePages($request);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin/pages', $response->getTargetUrl());
    }
}