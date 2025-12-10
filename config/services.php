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
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
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

    // Der zentrale Konfigurations-Service
    $container->register('config_service', ConfigService::class)
        ->addArgument(new Reference(MrWo\Nexus\Repository\ConfigRepositoryInterface::class)) // Injiziertes Repo
        ->setPublic(true);

    // Factory für Session-Handler
    $container->register('session_factory', MrWo\Nexus\Service\SessionFactory::class)
        ->addArgument(new Reference('config_service'));

    // Der Handler selbst (erzeugt durch Factory)
    $container->register('session_handler_instance', \SessionHandlerInterface::class)
        ->setFactory([new Reference('session_factory'), 'createHandler']);

    // Der zentrale Session-Service
    $container->register('session_service', SessionService::class)
        ->addArgument(new Reference('config_service')) // Injiziere ConfigService für Lifetime & Salt
        ->addArgument(new Reference('session_handler_instance')) // Injizierter Handler
        ->addArgument(new Reference('security_logger')) // <--- Security-Logger
        ->setPublic(true);

    // Der Service zur Verwaltung der Benutzerzustimmung
    $container->register('consent_service', ConsentService::class)
        ->addArgument(new Reference('session_service')) // Benötigt den Session-Service
        ->setPublic(true);
        
    // 1. Der File-Provider
    $container->register(MrWo\Nexus\Service\Provider\PhpFileTranslationProvider::class, MrWo\Nexus\Service\Provider\PhpFileTranslationProvider::class)
        ->addArgument($projectDir);

    // 2. Der Translator Service (neu verkabelt)
    $container->register('translator_service', TranslatorService::class)
        ->addArgument(new Reference('session_service'))
        ->addMethodCall('addProvider', [new Reference(MrWo\Nexus\Service\Provider\PhpFileTranslationProvider::class)])
        ->setPublic(true);

    // Der Service für Assets (Manifest, Dev/Prod automatisch)
    $container->register('asset_service', AssetService::class)
        ->setPublic(true);

    // Helper um ENV-Variablen zu laden (Fallback auf Server-Vars)
    $getEnv = fn($key) => $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    // Der Authentifizierungs-Service (Login-Logik)
    $container->register(AuthenticationService::class, AuthenticationService::class)
        ->addArgument(new Reference('session_service'))
        ->addArgument(new Reference(MrWo\Nexus\Repository\UserRepositoryInterface::class)) // Injiziertes Repo
        ->addArgument(new Reference('security_logger')) // Security-Logger
        ->setPublic(true);

    // Der Service für Dummy-Seiten und Sitemap
    $container->register(PageManagerService::class, PageManagerService::class)
        ->addArgument(new Reference(MrWo\Nexus\Repository\PageRepositoryInterface::class)) // Injiziertes Repo
        ->addArgument($projectDir) // Für Sitemap-Pfad
        ->setPublic(true);

    // Der Security Logger
    $container->register('security_logger', MrWo\Nexus\Service\SecurityLogger::class)
        ->setPublic(true);

    // --- SERVICES ---
    
    // API Token Authenticator
    $container->register(MrWo\Nexus\Service\ApiTokenAuthenticator::class, MrWo\Nexus\Service\ApiTokenAuthenticator::class)
        ->addArgument(new Reference(MrWo\Nexus\Repository\ApiTokenRepositoryInterface::class))
        ->setPublic(true);

    // Datenbank-Service (PDO Wrapper)
    $container->register('database_service', MrWo\Nexus\Service\DatabaseService::class)
        ->addArgument(new Reference('config_service'))
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

    // Der Controller für die Sprachumschaltung
    $container->register(MrWo\Nexus\Controller\LanguageController::class, MrWo\Nexus\Controller\LanguageController::class)
        ->addArgument(new Reference('session_service')) // Benötigt Session Service zum Schreiben des Attribute Bags
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

    // API V1 Status Controller
    $container->register(MrWo\Nexus\Controller\Api\V1\StatusController::class, MrWo\Nexus\Controller\Api\V1\StatusController::class)
        ->setPublic(true);

    // =========================================================================
    // REPOSITORIES
    // =========================================================================

    // 1. Der Env-Provider (explizit unter eigenem Namen registriert und getaggt)
    $container->register(MrWo\Nexus\Repository\EnvUserRepository::class, MrWo\Nexus\Repository\EnvUserRepository::class)
        ->addArgument($getEnv('ADMIN_USER'))
        ->addArgument($getEnv('ADMIN_EMAIL'))
        ->addArgument($getEnv('ADMIN_PASSWORD_HASH'))
        ->addTag('nexus.user_provider')
        ->setPublic(true);

    // 2. Die Chain (Sammelt alle Provider)
    // Wir registrieren die Chain ALS die Implementierung für das Interface.
    $container->register(MrWo\Nexus\Repository\UserRepositoryInterface::class, MrWo\Nexus\Repository\ChainUserRepository::class)
        ->addArgument(new TaggedIteratorArgument('nexus.user_provider'))
        ->setPublic(true);

    // Das File-basierte Page-Repository
    $container->register(MrWo\Nexus\Repository\PageRepositoryInterface::class, MrWo\Nexus\Repository\FilePageRepository::class)
        ->addArgument($projectDir);

    // Das File-basierte Config-Repository
    $container->register(MrWo\Nexus\Repository\ConfigRepositoryInterface::class, MrWo\Nexus\Repository\FileConfigRepository::class)
        ->addArgument($projectDir);

    // API Token Repository (Env Implementation)
    $container->register(MrWo\Nexus\Repository\ApiTokenRepositoryInterface::class, MrWo\Nexus\Repository\EnvApiTokenRepository::class)
        ->addArgument($getEnv('APP_SECRET'))
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
    // Umsetzung ADR 011: Environment-Awareness für Cache & Debugging
    $container->register(Environment::class, Environment::class)
        ->addArgument(new Reference('twig.loader')) // 1. Argument: Loader
        ->addArgument([                             // 2. Argument: Optionen
            'debug' => $getEnv('APP_ENV') === 'development',
            'cache' => ($getEnv('APP_ENV') === 'development') ? false : $projectDir . '/var/cache/twig',
            'auto_reload' => true,
            'strict_variables' => ($getEnv('APP_ENV') === 'development'),
        ])
        ->addMethodCall('addExtension', [new Reference('twig.app_extension')])
        ->setPublic(true);
};