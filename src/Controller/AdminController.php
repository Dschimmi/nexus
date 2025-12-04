<?php

declare(strict_types=1);

namespace MrWo\Nexus\Controller;

use MrWo\Nexus\Service\AuthenticationService;
use MrWo\Nexus\Service\ConfigService;
use MrWo\Nexus\Service\PageManagerService;
use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\TranslatorService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Controller für den geschützten Admin-Bereich.
 * 
 * Dieser Controller verwaltet alle administrativen Aufgaben:
 * - Authentifizierung (Login/Logout)
 * - Dashboard-Anzeige
 * - Konfiguration der Module (Feature Toggles)
 * - Erstellung von Dummy-Seiten
 */
class AdminController
{
    /**
     * @param Environment           $twig          Die Twig Template-Engine.
     * @param AuthenticationService $authService   Service zur Prüfung der Admin-Zugangsdaten.
     * @param ConfigService         $configService Service zum Lesen und Schreiben der module.json.
     * @param TranslatorService     $translator    Service für Internationalisierung (Fehlermeldungen etc.).
     * @param PageManagerService    $pageManager   Service zur Verwaltung der physischen Dummy-Dateien.
     * @param SessionService        $session       Service für Session-Handling und Flash-Messages.
     */
    public function __construct(
        private Environment $twig,
        private AuthenticationService $authService,
        private ConfigService $configService,
        private TranslatorService $translator,
        private PageManagerService $pageManager,
        private SessionService $session
    ) {}

    /**
     * Zeigt das Admin-Dashboard oder das Login-Formular an.
     *
     * @return Response Das gerenderte HTML.
     */
    public function index(): Response
    {
        // Zugriffsschutz: Prüfen, ob der User Admin-Rechte hat.
        if (!$this->authService->isAdmin()) {
            // Falls nicht eingeloggt, generisches Login-Formular rendern.
            return new Response($this->twig->render('forms/login.html.twig', [
                'page_title'  => 'Admin Login',
                'form_action' => '/admin/login'
            ]));
        }

        // Falls eingeloggt, Dashboard mit aktuellen Konfigurationsdaten anzeigen.
        return new Response($this->twig->render('admin/dashboard.html.twig', [
            'modules' => $this->configService->getAll(),
            'user'    => $this->authService->getUser()
        ]));
    }

    /**
     * Verarbeitet den Login-Versuch (POST-Request).
     *
     * @param Request $request Der HTTP-Request mit den Formulardaten.
     * @return Response Ein Redirect bei Erfolg oder das Formular mit Fehler bei Misserfolg.
     */
    public function login(Request $request): Response
    {
        $identifier = $request->request->get('username', '');
        $password = $request->request->get('password', '');
        
        // Prüfung der Credentials an den Service delegieren.
        if ($this->authService->login($identifier, $password)) {
            // Bei Erfolg: Redirect zum Dashboard.
            return new RedirectResponse('/admin');
        }

        // Bei Misserfolg: Fehlertext übersetzen.
        $errorMessage = $this->translator->translate('login.error_auth');

        // Formular erneut anzeigen, Username vorbefüllen (UX).
        return new Response($this->twig->render('forms/login.html.twig', [
            'page_title'    => 'Admin Login',
            'form_action'   => '/admin/login',
            'error'         => $errorMessage,
            'last_username' => $identifier
        ]));
    }

    /**
     * Loggt den Benutzer aus und invalidiert die Session.
     *
     * @return Response Redirect zur Admin-Startseite (Login).
     */
    public function logout(): Response
    {
        $this->authService->logout();
        return new RedirectResponse('/admin');
    }

    /**
     * Speichert die Modul-Konfiguration aus dem Dashboard.
     *
     * @param Request $request Der HTTP-Request mit den Checkbox-Werten.
     * @return Response Redirect zurück zum Dashboard.
     */
    public function saveConfig(Request $request): Response
    {
        // Zugriffsschutz
        if (!$this->authService->isAdmin()) {
            return new RedirectResponse('/admin');
        }

        // Feature-Toggles aktualisieren.
        // Hinweis: Checkboxen senden nur Daten, wenn sie aktiv sind ("on").
        // $request->request->has(...) gibt true zurück, wenn der Key existiert.
        $this->configService->set('module_user_management', $request->request->has('module_user_management'));
        $this->configService->set('module_site_search', $request->request->has('module_site_search'));
        $this->configService->set('module_cookie_banner', $request->request->has('module_cookie_banner'));
        $this->configService->set('module_language_selection', $request->request->has('module_language_selection'));

        // Erfolgsmeldung für den Benutzer setzen (Flash-Message).
        // Diese wird beim nächsten Seitenaufruf einmalig angezeigt.
        $this->session->addFlash(
            'success', 
            $this->translator->translate('admin.save_btn') . ' OK' // Provisorischer Text, idealerweise eigener Key
        );

        return new RedirectResponse('/admin');
    }

    /**
     * Handhabt das Erstellen neuer Dummy-Seiten.
     * Unterstützt GET (Formular anzeigen) und POST (Seite speichern).
     *
     * @param Request $request Der HTTP-Request.
     * @return Response Das Formular oder ein Redirect.
     */
    /**
     * Handhabt das Erstellen neuer Dummy-Seiten.
     * Unterstützt GET (Formular anzeigen) und POST (Seite speichern).
     */
    public function createPage(Request $request): Response
    {
        // Zugriffsschutz
        if (!$this->authService->isAdmin()) {
            return new RedirectResponse('/admin');
        }

        // POST-Request: Formular wurde abgesendet
        if ($request->isMethod('POST')) {
            $slug = $request->request->get('slug', '');
            $title = $request->request->get('title', '');
            $content = $request->request->get('content', '');

            try {
                // Versuchen, die Seite über den Service zu erstellen.
                $this->pageManager->createPage($slug, $title, $content);
                
                // Erfolgsmeldung in die Session schreiben.
                $this->session->addFlash(
                    'success', 
                    $this->translator->translate('admin.page_success')
                );
                
                // Zurück zum Dashboard leiten.
                return new RedirectResponse('/admin');

            } catch (\RuntimeException $e) {
                // Fehler beim Speichern: Formular erneut rendern und Fehlermeldung anzeigen.
                return new Response($this->twig->render('admin/page_create.html.twig', [
                    'error'        => $e->getMessage(),
                    'last_title'   => $title,
                    'last_slug'    => $slug,
                    'last_content' => $content,
                    'user'         => $this->authService->getUser() // User-Daten für Header übergeben
                ]));
            }
        }

        // GET-Request: Leeres Formular anzeigen.
        return new Response($this->twig->render('admin/page_create.html.twig', [
            'user' => $this->authService->getUser() // User-Daten für Header übergeben
        ]));
    }

    /**
     * Zeigt eine Liste aller Dummy-Seiten zur Verwaltung an.
     */
    public function listPages(): Response
    {
        // Zugriffsschutz
        if (!$this->authService->isAdmin()) {
            return new RedirectResponse('/admin');
        }

        // Seiten laden
        $pages = $this->pageManager->getPages();

        return new Response($this->twig->render('admin/pages_list.html.twig', [
            'pages' => $pages,
            'user'  => $this->authService->getUser() // Damit der Header stimmt
        ]));
    }

    /**
     * Löscht ausgewählte Seiten (POST-Request).
     */
    public function deletePages(Request $request): Response
    {
        // Zugriffsschutz
        if (!$this->authService->isAdmin()) {
            return new RedirectResponse('/admin');
        }

        // Slugs aus dem Formular holen (erwartet name="slugs[]")
        $payload = $request->request->all();
        $slugs = $payload['slugs'] ?? [];

        if (!empty($slugs) && is_array($slugs)) {
            $count = $this->pageManager->deletePages($slugs);
            
            if ($count > 0) {
                $this->session->addFlash(
                    'success', 
                    $this->translator->translate('admin.pages_delete_ok') . " ($count)"
                );
            }
        } else {
            // Optional: Info, dass nichts ausgewählt war
            $this->session->addFlash(
                'info', 
                'Keine Seiten ausgewählt.'
            );
        }

        // Zurück zur Liste
        return new RedirectResponse('/admin/pages');
    }
}