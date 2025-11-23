<?php

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

// Definiert die Route für die Startseite ("/")
$routes->add('homepage', new Route(
    path: '/',
    defaults: ['_controller' => 'MrWo\Nexus\Controller\HomepageController']
));

return $routes;