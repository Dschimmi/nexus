<?php

namespace MrWo\Nexus\Controller;

use MrWo\Nexus\Attribute\IsPublic;
use MrWo\Nexus\Infrastructure\Consent\ConsentService;
use MrWo\Nexus\Infrastructure\Session\SessionService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Behandelt Anfragen zur Verwaltung der Benutzerzustimmung (Cookie-Consent).
 */

#[isPublic]
class ConsentController
{
    /**
     * Der DI-Container wird diesen Konstruktor aufrufen und automatisch
     * eine Instanz des 'consent_service' übergeben.
     *
     * @param ConsentService $consentService
     */
    public function __construct(
        private ConsentService $consentService,
        private SessionService $session
    ) {}

    /**
     * Akzeptiert alle optionalen Cookie-Kategorien.
     *
     * @param Request $request Das aktuelle Request-Objekt.
     * @return Response Eine Redirect-Antwort.
     */
    public function accept(Request $request): Response
    {
        // CSRF Check (Token aus Header oder Body)
        $token = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->session->isCsrfTokenValid('consent_action', $token)) {
             return new Response('Invalid CSRF Token', 403);
        }

        $this->consentService->grantConsent('marketing');
        $this->consentService->grantConsent('statistics');

        // Hole die URL der vorherigen Seite aus den Request-Headern.
        // Als Fallback, falls kein Referer gesendet wurde, nutze die Startseite ('/').
        $referer = $request->headers->get('referer', '/');
        
        // Leite den Benutzer zur Startseite zurück.
        return new RedirectResponse($referer);
    }

    /**
     * Lehnt alle optionalen Cookie-Kategorien ab.
     *
     * @param Request $request Das aktuelle Request-Objekt.
     * @return Response Eine Redirect-Antwort.
     */
    public function decline(Request $request): Response
    {
        // CSRF Check (Token aus Header oder Body)
        $token = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->session->isCsrfTokenValid('consent_action', $token)) {
             return new Response('Invalid CSRF Token', 403);
        }

        $this->consentService->revokeConsent('marketing');
        $this->consentService->revokeConsent('statistics');

        // Hole die URL der vorherigen Seite aus den Request-Headern.
        // Als Fallback, falls kein Referer gesendet wurde, nutze die Startseite ('/').
        $referer = $request->headers->get('referer', '/');
        
        // Leite den Benutzer zur Startseite zurück.
        return new RedirectResponse($referer);
    }
}