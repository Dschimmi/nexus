<?php

namespace MrWo\Nexus\Kernel;

use MrWo\Nexus\Service\TranslatorService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Throwable;
use Tracy\Debugger;
use Twig\Environment;

/**
 * Der zentrale Kernel der Anwendung.
 * Er ist der Einstiegspunkt für die Verarbeitung jeder Anfrage.
 */
class Kernel
{
    /**
     * @var string Die aktuelle Anwendungsumgebung (z.B. 'development' oder 'production').
     */
    private string $appEnv;

    // CSP Nonce und Regel
    /** @var string Der Zufallswert für die CSP Nonce (Number used once). */
    private string $cspNonce;
    
    /** @var string Standard CSP-Regel. Wird in der Regel von der Config überschrieben. */
    private const DEFAULT_CSP = "default-src 'self'; style-src 'self' 'nonce-CSP_NONCE'; script-src 'self' 'nonce-CSP_NONCE'";

    /**
     * @var ContainerBuilder Der zentrale DI-Container für alle Services.
     */
    private ContainerBuilder $container;

    /**
     * @param string $appEnv Die aktuelle Anwendungsumgebung.
     */
    public function __construct(string $appEnv)
    {
        $this->appEnv = $appEnv;
        $this->container = new ContainerBuilder();
        $this->cspNonce = $this->generateCspNonce(); // CSP Nonce generieren
    }

    // HINZUFÜGEN START: CSP-Hilfsmethoden
    /**
     * Generiert einen Base64-kodierten, kryptografisch sicheren Zufallswert
     * für die Content Security Policy Nonce.
     * @return string
     */
    private function generateCspNonce(): string
    {
        // 16 Bytes * 8 Bits/Byte = 128 Bits Entropie
        return base64_encode(random_bytes(16));
    }

    /**
     * Setzt den CSP-Header und ersetzt den Nonce-Placeholder sowie weitere
     * wichtige, gehärtete Sicherheits-Header (OWASP A05/A03/A07).
     * @param Response $response
     * @return void
     */
    private function setSecurityHeaders(Response $response): void
    {
        /** @var \MrWo\Nexus\Service\ConfigService $configService */
        // ConfigService aus dem kompilierten Container abrufen.
        $configService = $this->container->get(\MrWo\Nexus\Service\ConfigService::class);
        
        // 1. Content Security Policy (CSP)
        // HOLEN: Holen Sie die CSP-Policy aus dem ConfigService anstelle des Hardcodes.
        $csp = $configService->get('security.content_security_policy', self::DEFAULT_CSP);
        
        // Ersetze den Platzhalter im CSP-Header durch den generierten Nonce-Wert
        $policy = str_replace('CSP_NONCE', $this->cspNonce, $csp);
        
        $response->headers->set('Content-Security-Policy', $policy);
        
        // 2. Weitere Härtungs-Header hinzufügen (Best Practice)
        
        // Anti-Clickjacking: Frames nur vom eigenen Ursprung erlauben
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN'); 
        
        // MIME-Sniffing verhindern (XSS-Vektor)
        $response->headers->set('X-Content-Type-Options', 'nosniff'); 
        
        // Moderne Referrer-Policy: Datenschutzerklärung für Benutzer
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Nur über HTTPS zugänglich machen, wenn einmal besucht (HSTS)
        // WICHTIG: Nur in der Produktivumgebung setzen!
        if ($this->appEnv !== 'development') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
    }

    /**
     * Verarbeitet eine Anfrage und gibt eine Antwort zurück.
     * Dies ist die Haupt-Ablaufkette.
     *
     * @param Request $request Das eingehende Request-Objekt.
     * @return Response Das ausgehende Response-Objekt.
     * @throws Throwable Wenn im Entwicklungsmodus ein Fehler auftritt, wird er an Tracy weitergereicht.
     */
    public function handleRequest(Request $request): Response
    {
        // Lade die Service-Konfigurations-Funktion und führe sie aus, um den Container zu füllen.
        $configureContainer = require_once __DIR__ . '/../../config/services.php';
        $configureContainer($this->container);
   
        $this->container->compile();

        // Starte die Session über den Service aus dem Container.
        $sessionService = $this->container->get('session_service');
        
        // Kontextsteuerung (Ticket 26): Keine automatische Session für CLI oder API.
        // API-Requests (/api/...) sind stateless und nutzen Tokens statt Sessions.
        $isCli = (php_sapi_name() === 'cli');
        $isApiRequest = str_starts_with($request->getPathInfo(), '/api/');

        if (!$isCli && !$isApiRequest) {
            $sessionService->start();
        }
        
        // --- NEU: Globaler Security Check (Anti-Replay) ---
        /** @var \MrWo\Nexus\Service\AuthenticationService $authService */
        $authService = $this->container->get(\MrWo\Nexus\Service\AuthenticationService::class);
        
        // Wenn ein User eingeloggt ist, prüfen wir die Integrität (nur wenn Session läuft)
        if (!$isCli && !$isApiRequest && $authService->getUser()) {
            if (!$authService->validateSessionUser()) {
                // Wenn Check fehlschlägt (wurde ausgeloggt), Redirect zur Login-Seite erzwingen?
                // Oder wir lassen ihn weiterlaufen, er ist ja jetzt ausgeloggt.
                // Redirect wäre sauberer, aber wir sind im Kernel vor dem Router.
                // Wir belassen es beim Logout. Der nächste Controller-Aufruf (Admin) wird merken, dass er weg ist.
            }
        }

        // Wir stellen das 'app' Objekt global für Twig bereit (für app.request.pathInfo etc.)
        /** @var Environment $twig */
        $twig = $this->container->get(Environment::class);
        
        // Globales App-Objekt um die CSP Nonce erweitern (Ticket 34)
        $twig->addGlobal('app', [
            'request' => $request,
            'csp_nonce' => $this->cspNonce, 
        ]);

        // Initialisiere die Sprache (i18n) basierend auf der Session/Config
        /** @var TranslatorService $translator */
        $translator = $this->container->get('translator_service');

        try {
            // Finde die passende Route für die Anfrage.
            $request->attributes->add($this->resolveRoute($request));

            // Finde den zuständigen Controller über den container-fähigen Resolver.
            $controllerResolver = new ContainerControllerResolver($this->container);
            $controller = $controllerResolver->getController($request);
            
            // Ermittle die benötigten Argumente für die Controller-Methode.
            $argumentResolver = new ArgumentResolver();
            $arguments = $argumentResolver->getArguments($request, $controller);

            // Führe den Controller mit den ermittelten Argumenten aus.
            $response = call_user_func_array($controller, $arguments);

        } catch (ResourceNotFoundException $e) {

            // Logge den 404-Fehler mit geringerer Priorität.
            Debugger::log($e, Debugger::WARNING);

            // Im DEV-Modus soll Tracy den Fehler anzeigen.
            if ($this->appEnv === 'development') {
                throw $e;
            }

            // Im PROD-Modus: Elegante 404-Seite rendern.
            /** @var Environment $twig */
            $twig = $this->container->get(Environment::class);
            
            $content = $twig->render('error.html.twig', [
                'error_type' => '404'
            ]);

            $response = new Response($content, 404);

        } catch (Throwable $e) {

            // Logge den 500-Fehler mit höchster Priorität.
            Debugger::log($e, Debugger::ERROR);

            // Im DEV-Modus soll Tracy den Fehler anzeigen.
            if ($this->appEnv === 'development') {
                throw $e;
            }
            // Im PROD-Modus geben wir eine einfache 500-Seite aus.
            $response = new Response('Ein Fehler ist aufgetreten', 500);
        } finally {
            // WICHTIG: Session-Daten zurückschreiben, egal ob Erfolg oder Fehler.
            // Aber nur, wenn wir überhaupt eine Session haben (kein CLI/API).
            if (!$isCli && !$isApiRequest) {
                $sessionService->save();
            }
        }

        $this->setSecurityHeaders($response); // Security Header zur Response hinzufügen (Ticket 34)

        return $response;
    }

    /**
     * Findet die passende Route für die Anfrage.
     *
     * @param Request $request
     * @return array Die Routen-Parameter
     * @throws ResourceNotFoundException Wenn keine Route gefunden wird.
     */
    private function resolveRoute(Request $request): array
    {
        $routes = require_once __DIR__ . '/../../config/routes.php';
        $context = new RequestContext();
        $context->fromRequest($request);
        
        $matcher = new UrlMatcher($routes, $context);

        return $matcher->match($request->getPathInfo());
    }
}