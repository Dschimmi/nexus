<?php

use MrWo\Nexus\Controller\ConsentController;
use MrWo\Nexus\Controller\HomepageController;
use MrWo\Nexus\Controller\StaticPageController;
use MrWo\Nexus\Controller\DynamicPageController;
use MrWo\Nexus\Controller\AdminController;
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

// Impressum
$routes->add('imprint', new Route(
    path: '/impressum',
    defaults: ['_controller' => [StaticPageController::class, 'imprint']]
));

// Datenschutzerklärung
$routes->add('privacy', new Route(
    path: '/datenschutz',
    defaults: ['_controller' => [StaticPageController::class, 'privacy']]
));

// Kontakt
$routes->add('contact', new Route(
    path: '/kontakt',
    defaults: ['_controller' => [StaticPageController::class, 'contact']]
));

// --- ADMIN ROUTEN ---

// Admin Dashboard (oder Login-Maske, wenn nicht eingeloggt)
$routes->add('admin_dashboard', new Route(
    path: '/admin',
    defaults: ['_controller' => [AdminController::class, 'index']],
    methods: ['GET']
));

// Login verarbeiten (POST)
$routes->add('admin_login_process', new Route(
    path: '/admin/login',
    defaults: ['_controller' => [AdminController::class, 'login']],
    methods: ['POST']
));

// Logout
$routes->add('admin_logout', new Route(
    path: '/admin/logout',
    defaults: ['_controller' => [AdminController::class, 'logout']],
    methods: ['GET']
));

// Konfiguration speichern (POST)
$routes->add('admin_save_config', new Route(
    path: '/admin/config',
    defaults: ['_controller' => [AdminController::class, 'saveConfig']],
    methods: ['POST']
));

// Dummy-Seite erstellen (GET & POST)
$routes->add('admin_page_create', new Route(
    path: '/admin/pages/create',
    defaults: ['_controller' => [AdminController::class, 'createPage']],
    methods: ['GET', 'POST']
));

// Seiten verwalten (Liste)
$routes->add('admin_pages_list', new Route(
    path: '/admin/pages',
    defaults: ['_controller' => [AdminController::class, 'listPages']],
    methods: ['GET']
));

// Seiten löschen (POST)
$routes->add('admin_pages_delete', new Route(
    path: '/admin/pages/delete',
    defaults: ['_controller' => [AdminController::class, 'deletePages']],
    methods: ['POST']
));

/**
 * ============================================================================
 *  DYNAMISCHE SEITEN (Dummy-Pages)
 * ============================================================================
 * Fängt generische URLs wie /meine-seite ab und prüft auf existierende HTML-Dateien.
 * Muss VOR der Catch-All Route stehen, da diese sonst alles verschluckt.
 * Muss NACH spezifischen Routen (z.B. /admin) stehen, um Konflikte zu vermeiden.
 */
$routes->add('dynamic_page', new Route(
    path: '/{slug}',
    defaults: ['_controller' => [DynamicPageController::class, 'show']],
    requirements: ['slug' => '[a-z0-9-]+'] // Nur Kleinbuchstaben, Zahlen und Bindestriche
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