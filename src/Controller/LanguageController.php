<?php

declare(strict_types=1);

namespace MrWo\Nexus\Controller;

use MrWo\Nexus\Service\SessionService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller für die Sprachumschaltung.
 * 
 * Implementiert das PRG-Pattern (Post-Redirect-Get) für die Sprachwahl,
 * um State-Changes via GET-Requests (und "schmutzige" URLs mit ?lang=) zu vermeiden.
 */
class LanguageController
{
    /**
     * @param SessionService $session Der Session-Service zum Speichern der Sprachwahl.
     */
    public function __construct(
        private SessionService $session
    ) {}

    /**
     * Verarbeitet den POST-Request zur Sprachänderung.
     * 
     * @param Request $request Der HTTP-Request.
     * @return RedirectResponse Leitet zurück zur Ursprungsseite (Referer).
     */
    public function switch(Request $request): RedirectResponse
    {
        // 1. Gewünschte Sprache aus dem POST-Body holen
        $lang = $request->request->get('lang');
        
        // 2. Validierung gegen Whitelist (Hardcoded für Core, später via ConfigService möglich)
        // Wir erlauben nur definierte Sprachen, um Session-Pollution zu verhindern.
        if ($lang && in_array($lang, ['de', 'en'])) {
            // Speichern im Attribute-Bag der Session
            $this->session->getBag('attributes')->set('locale', $lang);
        }

        // 3. Redirect zurück zur Seite, von der der User kam.
        // Fallback auf Startseite '/', falls kein Referer Header gesendet wurde.
        $referer = $request->headers->get('referer', '/');
        
        return new RedirectResponse($referer);
    }
}