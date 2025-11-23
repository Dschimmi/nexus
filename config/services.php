<?php

use MrWo\Nexus\Controller\ConsentController;
use MrWo\Nexus\Controller\HomepageController;
use MrWo\Nexus\Service\ConsentService;
use MrWo\Nexus\Service\SessionService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/** @var ContainerBuilder $container */

// Wir definieren eine Funktion, die die Konfiguration vornimmt
return function(ContainerBuilder $container) {
    // SessionService als zentralen, geteilten Dienst registrieren
    $container->register('session_service', SessionService::class)
        ->setPublic(true);

    // ConsentService registrieren und ihm den SessionService als Abhängigkeit übergeben
    $container->register('consent_service', ConsentService::class)
        ->addArgument(new Reference('session_service'))
        ->setPublic(true);

    // Registriere Controller als "private" Dienste (Standard)
    // Mache sie "public", damit der ControllerResolver sie finden kann.
    $container->register(HomepageController::class, HomepageController::class)
        ->setPublic(true);

    $container->register(ConsentController::class, ConsentController::class)
        ->addArgument(new Reference('consent_service')) // Injiziere den ConsentService
        ->setPublic(true);
};