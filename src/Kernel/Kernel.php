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

        // Starte die Session über den Service aus dem Container.
        $this->container->get('session_service')->start();

        // Code zur Spracherkennung (wird hier eingefügt, sobald er benötigt wird).

        try {
            // Finde die passende Route für die Anfrage.
            $request->attributes->add($this->resolveRoute($request));

            // Finde den zuständigen Controller über den container-fähigen Resolver.
            $controllerResolver = new ContainerControllerResolver($this->container);
            $controller = $controllerResolver->getController($request);
            
            // Ermittle die benötigten Argumente für die Controller-Methode (z.B. Request-Objekt, Services).
            $argumentResolver = new ArgumentResolver();
            $arguments = $argumentResolver->getArguments($request, $controller);

            // Führe den Controller mit den ermittelten Argumenten aus.
            $response = call_user_func_array($controller, $arguments);

        } catch (ResourceNotFoundException $e) {

            // Logge den 404-Fehler mit geringerer Priorität.
            Debugger::log($e, Debugger::WARNING);

            // Im DEV-Modus soll Tracy den Fehler anzeigen, also werfen wir ihn einfach weiter.
            if ($this->appEnv === 'development') {
                throw $e;
            }
            // Im PROD-Modus geben wir eine einfache 404-Seite aus.
            $response = new Response('Seite nicht gefunden', 404);
        } catch (Throwable $e) {

            // Logge den 500-Fehler mit höchster Priorität.
            Debugger::log($e, Debugger::ERROR);

            // Im DEV-Modus soll Tracy den Fehler anzeigen, also werfen wir ihn einfach weiter.
            if ($this->appEnv === 'development') {
                throw $e;
            }
            // Im PROD-Modus geben wir eine einfache 500-Seite aus.
            $response = new Response('Ein Fehler ist aufgetreten', 500);
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