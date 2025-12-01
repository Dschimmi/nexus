<?php

use MrWo\Nexus\Controller\ConsentController;
use MrWo\Nexus\Controller\HomepageController;
use MrWo\Nexus\Controller\StaticPageController;
use MrWo\Nexus\Controller\DynamicPageController;
use MrWo\Nexus\Controller\AdminController;
use MrWo\Nexus\Service\AssetService;
use MrWo\Nexus\Service\ConfigService;
use MrWo\Nexus\Service\ConsentService;
use MrWo\Nexus\Service\SessionService;
use MrWo\Nexus\Service\TranslatorService;
use MrWo\Nexus\Service\AuthenticationService;
use MrWo\Nexus\Service\PageManagerService;
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
    
    // Basis-Pfad des Projekts ermitteln (eine Ebene über /config)
    $projectDir = dirname(__DIR__);

    // =========================================================================
    // SERVICES
    // =========================================================================

    // Der zentrale Konfigurations-Service (Verwaltet Feature-Toggles via modules.json)
    $container->register('config_service', ConfigService::class)
        ->addArgument($projectDir) // Übergibt den Pfad zum Projekt-Root
        ->setPublic(true);

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

    // Der Service für Assets (Manifest, Dev/Prod automatisch)
    $container->register('asset_service', AssetService::class)
        ->setPublic(true);

    // Helper um ENV-Variablen zu laden (Fallback auf Server-Vars)
    $getEnv = fn($key) => $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    // Der Authentifizierungs-Service (Login-Logik)
    $container->register(AuthenticationService::class, AuthenticationService::class)
        ->addArgument(new Reference('session_service'))
        ->addArgument($getEnv('ADMIN_USER'))         // Aus .env
        ->addArgument($getEnv('ADMIN_EMAIL'))        // Aus .env (Neu!)
        ->addArgument($getEnv('ADMIN_PASSWORD_HASH')) // Aus .env
        ->setPublic(true);

    // Der Service für Dummy-Seiten und Sitemap
    $container->register(PageManagerService::class, PageManagerService::class)
        ->addArgument($projectDir) // Projekt-Root Pfad
        ->setPublic(true);

    // =========================================================================
    // TWIG KONFIGURATION
    // =========================================================================

    // Definiert, wo Twig nach Template-Dateien suchen soll
    $container->register('twig.loader', FilesystemLoader::class)
        ->addArgument(__DIR__ . '/../templates');

    // Die benutzerdefinierte Twig Extension
    $container->register('twig.app_extension', AppExtension::class)
        ->addArgument(new Reference('translator_service'))
        ->addArgument(new Reference('asset_service'))
        ->addArgument(new Reference('config_service'))
        ->addArgument(new Reference('session_service'))
        ->addArgument(new Reference(PageManagerService::class)) // Neu: Für 'get_dummy_pages()'
        ->addTag('twig.extension');

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

    // Der Controller für statische Seiten (Impressum, Datenschutz)
    $container->register(StaticPageController::class, StaticPageController::class)
        ->addArgument(new Reference(Environment::class)) // Benötigt Twig
        ->setPublic(true);

    // Der Controller für dynamische Dummy-Seiten
    $container->register(DynamicPageController::class, DynamicPageController::class)
        ->addArgument(new Reference(Environment::class)) // Twig
        ->addArgument($projectDir)                       // Pfad zum Projekt-Root
        ->setPublic(true);
    
    // Der Controller für den Admin-Bereich
    $container->register(AdminController::class, AdminController::class)
        ->addArgument(new Reference(Environment::class))            // Twig
        ->addArgument(new Reference(AuthenticationService::class))  // Auth Service
        ->addArgument(new Reference('config_service'))              // Config Service
        ->addArgument(new Reference('translator_service'))          // Translator Service
        ->addArgument(new Reference(PageManagerService::class))     // PageManager Service
        ->addArgument(new Reference('session_service'))             // Neu: Session Service für Flash-Messages
        ->setPublic(true);
};