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
        $sessionService->start();

        // Wir stellen das 'app' Objekt global für Twig bereit (für app.request.pathInfo etc.)
        /** @var Environment $twig */
        $twig = $this->container->get(Environment::class);
        $twig->addGlobal('app', ['request' => $request]);

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
            // Ohne diesen Aufruf gehen Flash-Messages im Fehlerfall verloren.
            $sessionService->save();
        }

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