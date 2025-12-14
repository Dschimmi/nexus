<?php

use MrWo\Nexus\Controller\ConsentController;
use MrWo\Nexus\Controller\HomepageController;
use MrWo\Nexus\Controller\StaticPageController;
use MrWo\Nexus\Controller\DynamicPageController;
use MrWo\Nexus\Controller\AdminController;
use MrWo\Nexus\Controller\LanguageController;
use MrWo\Nexus\Controller\Api\V1\StatusController;

// Infrastructure Services (Verschoben)
use MrWo\Nexus\Infrastructure\Asset\AssetService;
use MrWo\Nexus\Infrastructure\Config\ConfigService;
use MrWo\Nexus\Infrastructure\Consent\ConsentService;
use MrWo\Nexus\Infrastructure\Session\SessionService;
use MrWo\Nexus\Infrastructure\Session\SessionFactory;
use MrWo\Nexus\Infrastructure\Translation\TranslatorService;
use MrWo\Nexus\Infrastructure\Translation\Provider\PhpFileTranslationProvider;
use MrWo\Nexus\Infrastructure\Security\SecurityLogger;
use MrWo\Nexus\Infrastructure\Security\RateLimiter;
use MrWo\Nexus\Infrastructure\Security\ApiTokenAuthenticator; // Wurde verschoben? Checken wir gleich.
use MrWo\Nexus\Infrastructure\Database\DatabaseService;
use MrWo\Nexus\Infrastructure\Persistence\EnvUserRepository;
use MrWo\Nexus\Infrastructure\Persistence\FilePageRepository;


// Application Services (Verschoben)
use MrWo\Nexus\Application\Auth\AuthenticationService;
use MrWo\Nexus\Application\Page\PageManager;
use MrWo\Nexus\Application\Page\PageRepositoryInterface;

// Domain Interfaces
use MrWo\Nexus\Domain\User\UserRepositoryInterface;


// Alte Repository Location (Nicht verschoben)
use MrWo\Nexus\Repository\ConfigRepositoryInterface; 
use MrWo\Nexus\Infrastructure\Persistence\ChainUserRepository;
use MrWo\Nexus\Repository\ApiTokenRepositoryInterface;
use MrWo\Nexus\Repository\EnvApiTokenRepository;
use MrWo\Nexus\Repository\InMemoryRateLimit;
use MrWo\Nexus\Repository\DatabaseRateLimit;
use MrWo\Nexus\Repository\RateLimitFactory;
use MrWo\Nexus\Repository\RateLimitInterface;

use MrWo\Nexus\Twig\AppExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

return function(ContainerBuilder $container) {
    
    $projectDir = dirname(__DIR__);

    // =========================================================================
    // INFRASTRUCTURE SERVICES
    // =========================================================================

    $container->register('config_service', ConfigService::class)
        ->addArgument(new Reference(ConfigRepositoryInterface::class))
        ->setPublic(true);

    $container->setAlias(ConfigService::class, 'config_service')->setPublic(true);

    $container->register('session_factory', SessionFactory::class)
        ->addArgument(new Reference('config_service'));

    $container->register('session_handler_instance', \SessionHandlerInterface::class)
        ->setFactory([new Reference('session_factory'), 'createHandler']);

    $container->register('session_service', SessionService::class)
        ->addArgument(new Reference('config_service'))
        ->addArgument(new Reference('session_handler_instance'))
        ->addArgument(new Reference('security_logger'))
        ->setPublic(true);

    $container->register('consent_service', ConsentService::class)
        ->addArgument(new Reference('session_service'))
        ->setPublic(true);
        
    $container->register(PhpFileTranslationProvider::class, PhpFileTranslationProvider::class)
        ->addArgument($projectDir);

    $container->register('translator_service', TranslatorService::class)
        ->addArgument(new Reference('session_service'))
        ->addMethodCall('addProvider', [new Reference(PhpFileTranslationProvider::class)])
        ->setPublic(true);

    $container->register('asset_service', AssetService::class)
        ->setPublic(true);

    $getEnv = fn($key) => $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    $container->register('security_logger', SecurityLogger::class)
        ->setPublic(true);

    $container->register('database_service', DatabaseService::class)
        ->addArgument(new Reference('config_service'))
        ->setPublic(true);
        
    // API Token Authenticator (Prüfen wo er liegt: Service oder Infra?)
    // Annahme: Wurde nach Infrastructure verschoben.
    // Falls nicht: use MrWo\Nexus\Service\ApiTokenAuthenticator; nutzen.
    $container->register(MrWo\Nexus\Infrastructure\Security\ApiTokenAuthenticator::class, MrWo\Nexus\Infrastructure\Security\ApiTokenAuthenticator::class)
        ->addArgument(new Reference(ApiTokenRepositoryInterface::class))
        ->setPublic(true);

    // Queue Interface (Sync Implementation as Default)
    $container->register(MrWo\Nexus\Domain\Queue\QueueInterface::class, MrWo\Nexus\Infrastructure\Queue\SyncQueue::class)
        ->setPublic(true);

    // =========================================================================
    // APPLICATION SERVICES
    // =========================================================================

    $container->register(AuthenticationService::class, AuthenticationService::class)
        ->addArgument(new Reference('session_service'))
        ->addArgument(new Reference(UserRepositoryInterface::class))
        ->addArgument(new Reference('security_logger'))
        ->addArgument(new Reference(RateLimiter::class))
        ->setPublic(true);

    $container->register(PageManager::class, PageManager::class)
        ->addArgument(new Reference(PageRepositoryInterface::class))
        ->addArgument($projectDir)
        ->setPublic(true);

    // =========================================================================
    // RATE LIMITING
    // =========================================================================

    $container->register('rate_limit.in_memory', InMemoryRateLimit::class)->setPublic(true); 
    
    $container->register('rate_limit.database', DatabaseRateLimit::class)
        ->addArgument(new Reference('database_service'))
        ->setPublic(true); 
        
    $container->register(RateLimitFactory::class, RateLimitFactory::class)->setPublic(true); 
    
    $container->register(RateLimitInterface::class, RateLimitInterface::class)
        ->setFactory([RateLimitFactory::class, 'createRateLimit'])
        ->addArgument(new Reference('service_container'))
        ->setPublic(true);
    
    $container->register(RateLimiter::class, RateLimiter::class)
        ->addArgument(new Reference('config_service'))
        ->addArgument(new Reference(RateLimitInterface::class))
        ->addArgument(new Reference('security_logger'))
        ->setPublic(true);

    // =========================================================================
    // CONTROLLERS
    // =========================================================================

    $container->register(HomepageController::class, HomepageController::class)
        ->addArgument(new Reference(Environment::class))
        ->setPublic(true);
        
    $container->register(ConsentController::class, ConsentController::class)
        ->addArgument(new Reference('consent_service'))
        ->setPublic(true);

    $container->register(LanguageController::class, LanguageController::class)
        ->addArgument(new Reference('session_service'))
        ->setPublic(true);

    $container->register(StaticPageController::class, StaticPageController::class)
        ->addArgument(new Reference(Environment::class))
        ->setPublic(true);

    $container->register(DynamicPageController::class, DynamicPageController::class)
        ->addArgument(new Reference(Environment::class))
        ->addArgument($projectDir)
        ->setPublic(true);
    
    $container->register(AdminController::class, AdminController::class)
        ->addArgument(new Reference(Environment::class))
        ->addArgument(new Reference(AuthenticationService::class))
        ->addArgument(new Reference('config_service'))
        ->addArgument(new Reference('translator_service'))
        ->addArgument(new Reference(PageManager::class))
        ->addArgument(new Reference('session_service'))
        ->setPublic(true);

    $container->register(StatusController::class, StatusController::class)
        ->setPublic(true);

    // =========================================================================
    // REPOSITORIES (Persistence & Adapters)
    // =========================================================================

    // Env User Repo
    $container->register(EnvUserRepository::class, EnvUserRepository::class)
        ->addArgument($getEnv('ADMIN_USER'))
        ->addArgument($getEnv('ADMIN_EMAIL'))
        ->addArgument($getEnv('ADMIN_PASSWORD_HASH'))
        ->addTag('nexus.user_provider')
        ->setPublic(true);

    // Chain Repo (nutzt Domain Interface als Key)
    $container->register(UserRepositoryInterface::class, ChainUserRepository::class)
        ->addArgument(new TaggedIteratorArgument('nexus.user_provider'))
        ->setPublic(true);

    // Page Repo (nutzt Domain Interface als Key)
    $container->register(PageRepositoryInterface::class, FilePageRepository::class)
        ->addArgument($projectDir);

    // Config Repo (File-Based implementation)
    $container->register(ConfigRepositoryInterface::class, MrWo\Nexus\Repository\FileConfigRepository::class)
        ->addArgument($projectDir);

    // Api Token Repo
    $container->register(ApiTokenRepositoryInterface::class, EnvApiTokenRepository::class)
        ->addArgument($getEnv('APP_SECRET'))
        ->setPublic(true);
    
    // =========================================================================
    // TWIG
    // =========================================================================

    $container->register('twig.loader', FilesystemLoader::class)
        ->addArgument(__DIR__ . '/../templates');

    $container->register('twig.app_extension', AppExtension::class)
        ->addArgument(new Reference('translator_service'))
        ->addArgument(new Reference('asset_service'))
        ->addArgument(new Reference('config_service'))
        ->addArgument(new Reference('session_service'))
        ->addArgument(new Reference(PageManager::class))
        ->addTag('twig.extension');

    $container->register(Environment::class, Environment::class)
        ->addArgument(new Reference('twig.loader'))
        ->addArgument([
            'debug' => $getEnv('APP_ENV') === 'development',
            'cache' => ($getEnv('APP_ENV') === 'development') ? false : $projectDir . '/var/cache/twig',
            'auto_reload' => true,
            'strict_variables' => ($getEnv('APP_ENV') === 'development'),
        ])
        ->addMethodCall('addExtension', [new Reference('twig.app_extension')])
        ->setPublic(true);

    // =========================================================================
    // MODULE LOADER (Ticket 42)
    // =========================================================================
    
    // Scanne den modules/ Ordner
    $modulesDir = $projectDir . '/modules';
    if (is_dir($modulesDir)) {
        $modules = scandir($modulesDir);
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') continue;
            
            // Konvention: Jedes Modul hat eine config/services.php
            $moduleConfig = $modulesDir . '/' . $module . '/config/services.php';
            if (file_exists($moduleConfig)) {
                // Importiere die Modul-Config
                // Nutze die importierten Klassen statt FQCN, das ist sauberer
                $loader = new PhpFileLoader($container, new FileLocator($modulesDir . '/' . $module . '/config'));
                $loader->load('services.php');
            }
        }
    }
};