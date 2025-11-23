<?php

namespace MrWo\Nexus\Kernel;

use MrWo\Nexus\Service\SessionService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Throwable;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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
     */
    public function handleRequest(Request $request): Response
    {
        // Lade die Service-Konfigurations-Funktion
        $configureContainer = require_once __DIR__ . '/../../config/services.php';

        // Führe die Funktion aus und übergib ihr unseren Container
        $configureContainer($this->container);

        // Starte die Session über den neu registrierten Service
        $this->container->get('session_service')->start();

        try {
            // 1. Finde die Route
            $request->attributes->add($this->resolveRoute($request));

            // 2. Erstelle den Controller mit dem neuen ControllerResolver
            // Neuer, container-bewusster ControllerResolver
            $controllerResolver = new ContainerControllerResolver($this->container);
            $controller = $controllerResolver->getController($request);

            // 3. Finde die Argumente für den Controller (z.B. Request-Objekt, Services)
            $argumentResolver = new ArgumentResolver();
            $arguments = $argumentResolver->getArguments($request, $controller);

            // 4. Führe den Controller mit den korrekten Argumenten aus
            $response = call_user_func_array($controller, $arguments);

        } catch (ResourceNotFoundException $e) {
            $response = $this->handleError(404, 'Seite nicht gefunden', $e);
        } catch (Throwable $e) {
            $response = $this->handleError(500, 'Interner Serverfehler', $e);
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

    /**
     * Erstellt eine standardisierte Fehler-Antwort mittels Twig-Template.
     *
     * @param int       $statusCode Der HTTP-Statuscode (z.B. 404, 500).
     * @param string    $statusText Die allgemeine Fehlermeldung (z.B. "Seite nicht gefunden").
     * @param Throwable $exception Die ausgelöste Exception für detaillierte Debug-Informationen.
     * @return Response
     */
    private function handleError(int $statusCode, string $statusText, Throwable $exception): Response
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $twig = new Environment($loader);

        $content = $twig->render('error.html.twig', [
            'status_code' => $statusCode,
            'status_text' => $statusText,
            'app_env'     => $this->appEnv,
            'exception'   => $exception,
            'title'       => "Fehler {$statusCode}"
        ]);

        return new Response($content, $statusCode);
    }
}