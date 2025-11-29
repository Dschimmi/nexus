<?php

use MrWo\Nexus\Controller\ConsentController;
use MrWo\Nexus\Controller\HomepageController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

// Route für die Startseite
$routes->add('homepage', new Route(
    path: '/',
    defaults: ['_controller' => HomepageController::class]
));

// Routen für die Consent-Aktionen
$routes->add('consent_accept', new Route(
    path: '/consent/accept',
    defaults: ['_controller' => [ConsentController::class, 'accept']]
));

$routes->add('consent_decline', new Route(
    path: '/consent/decline',
    defaults: ['_controller' => [ConsentController::class, 'decline']]
));

/**
 * ============================================================================
 *  CATCH-ALL Fallback-Route
 * ============================================================================
 * ALLE unbekannten URLs landen auf der Startseite.
 * Muss GANZ unten stehen, damit echte Routen nicht überschrieben werden.
 */
$routes->add('fallback', new Route(
    path: '/{any}',
    defaults: ['_controller' => HomepageController::class],
    requirements: ['any' => '.*']
));

return $routes;