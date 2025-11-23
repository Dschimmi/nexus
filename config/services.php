<?php

use MrWo\Nexus\Controller\ConsentController;
use MrWo\Nexus\Controller\HomepageController;
use MrWo\Nexus\Service\ConsentService;
use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\TranslatorService;
use MrWo\Nexus\Twig\AppExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Konfiguriert den Dependency Injection Container.
 *
 * @param ContainerBuilder $container Der zu konfigurierende Container.
 * @return void
 */
return function(ContainerBuilder $container) {
    
    // =========================================================================
    // SERVICES
    // =========================================================================

    // Der zentrale Session-Service
    $container->register('session_service', SessionService::class)
        ->setPublic(true);

    // Der Service zur Verwaltung der Benutzerzustimmung
    $container->register('consent_service', ConsentService::class)
        ->addArgument(new Reference('session_service')) // Benötigt den Session-Service
        ->setPublic(true);
        
    // Der Service für Übersetzungen
    $container->register('translator_service', TranslatorService::class)
        ->addArgument(new Reference('session_service')) // Benötigt den Session-Service
        ->setPublic(true);

    // =========================================================================
    // TWIG KONFIGURATION
    // =========================================================================

    // Definiert, wo Twig nach Template-Dateien suchen soll
    $container->register('twig.loader', FilesystemLoader::class)
        ->addArgument(__DIR__ . '/../templates');

    // Die benutzerdefinierte Twig Extension mit dem 'trans'-Filter
    $container->register('twig.app_extension', AppExtension::class)
        ->addArgument(new Reference('translator_service')) // Benötigt den Translator-Service
        ->addTag('twig.extension'); // Wichtig: Markiert dies als Twig Extension

    // Der zentrale Twig Environment Service
    $container->register(Environment::class, Environment::class)
        ->addArgument(new Reference('twig.loader')) // Benötigt den Loader
        ->addMethodCall('addExtension', [new Reference('twig.app_extension')]) // Fügt unsere Extension hinzu
        ->setPublic(true);

    // =========================================================================
    // CONTROLLER
    // =========================================================================

    // Der Controller für die Startseite
    $container->register(HomepageController::class, HomepageController::class)
        ->addArgument(new Reference(Environment::class)) // Benötigt Twig
        ->setPublic(true);
        
    // Der Controller für die Consent-Aktionen
    $container->register(ConsentController::class, ConsentController::class)
        ->addArgument(new Reference('consent_service')) // Benötigt den Consent-Service
        ->setPublic(true);
};