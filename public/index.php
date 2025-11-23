<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

// 1. Erstelle das Request-Objekt
$request = Request::createFromGlobals();

// 2. Lade unsere Routen-Definitionen
$routes = require_once __DIR__ . '/../config/routes.php';

// 3. Richte den URL Matcher ein
$context = new RequestContext();
$context->fromRequest($request);

$matcher = new UrlMatcher($routes, $context);

try {
    // 4. Versuche, eine passende Route für die aktuelle URL zu finden
    $parameters = $matcher->match($request->getPathInfo());

    // 5. Wenn eine Route gefunden wird, erstelle eine Instanz des Controllers und führe ihn aus
    $controllerClass = $parameters['_controller'];
    $controllerInstance = new $controllerClass();

    // Ruft die __invoke Methode auf
    $response = $controllerInstance();

} catch (ResourceNotFoundException $e) {
    // 6. Wenn keine Route passt, sende eine 404-Antwort
    $response = new \Symfony\Component\HttpFoundation\Response('404 Not Found', 404);
} catch (\Throwable $e) {
    // 7. Für alle anderen Fehler, sende eine 500-Antwort
    // (Dies wird später durch Tracy ersetzt)
    $response = new \Symfony\Component\HttpFoundation\Response('500 Internal Server Error', 500);
}

// 8. Sende die vom Controller generierte Antwort an den Browser
$response->send();