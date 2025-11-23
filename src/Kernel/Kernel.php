<?php

namespace MrWo\Nexus\Kernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Throwable;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Der zentrale Kernel der Anwendung.
 * Er ist der Einstiegspunkt für die Verarbeitung jeder Anfrage.
 */
class Kernel
{
    private string $appEnv;

    /**
     * @param string $appEnv Die aktuelle Anwendungsumgebung (z.B. 'development' oder 'production').
     */
    public function __construct(string $appEnv)
    {
        $this->appEnv = $appEnv;
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
        try {
            // 1. Finde die Route
            $parameters = $this->resolveRoute($request);

            // 2. Erstelle den Controller
            $controller = $this->resolveController($parameters);

            // 3. Führe den Controller aus und erhalte eine Response
            $response = $controller();

        } catch (ResourceNotFoundException $e) {
            // Fange speziell 404-Fehler (nicht gefundene Route) ab
            $response = $this->handleError(404, 'Seite nicht gefunden', $e);
        } catch (Throwable $e) {
            // Fange ALLE anderen Fehler (Exceptions, Errors) ab und behandle sie als 500
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
     * Erstellt eine Instanz des Controllers basierend auf den Routen-Parametern.
     *
     * @param array $parameters
     * @return callable Der aufrufbare Controller.
     */
    private function resolveController(array $parameters): callable
    {
        $controllerClass = $parameters['_controller'];

        return new $controllerClass();
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

        // Nutzt die saubere, übergebene Eigenschaft anstelle einer globalen Variable
        $content = $twig->render('error.html.twig', [
            'status_code' => $statusCode,
            'status_text' => $statusText,
            'app_env' => $this->appEnv,
            'exception' => $exception,
            'title' => "Fehler {$statusCode}"
        ]);

        return new Response($content, $statusCode);
    }
}