<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Tracy\Debugger;

// Aktiviere Tracy im Entwicklungsmodus.
// Der letzte Parameter ist der Pfad zum Log-Ordner.
Debugger::enable(Debugger::DEVELOPMENT, __DIR__ . '/../log');



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

    $response = $controllerInstance();

} catch (ResourceNotFoundException $e) {
    // 6. Wenn keine Route passt, sende eine 404-Antwort
    $response = new Response('404 Not Found', 404);
}

// 7. Sende die generierte Antwort an den Browser
$response->send();