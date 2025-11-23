<?php

// Definiere eine globale Konstante für das Projekt-Stammverzeichnis
define('PROJECT_ROOT', dirname(__DIR__));

require_once PROJECT_ROOT . '/vendor/autoload.php';

// 1. Lade die .env Datei
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use MrWo\Nexus\Kernel\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Tracy\Debugger;

// 2. Hole die Environment-Variable EINMALIG (Fail-Safe)
$appEnv = $_SERVER['APP_ENV'] ?? 'production';

// HTTPS-ERZWINGUNG 
if ($appEnv === 'production') {
    // Prüft, ob HTTPS aktiv ist. $_SERVER['HTTPS'] ist nur dann gesetzt und nicht 'off',
    // wenn die Verbindung sicher ist.
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        // Sende einen minimalen, aber klaren Fehler und breche sofort ab.
        header('HTTP/1.1 503 Service Unavailable');
        die('Sicherheitsfehler: Der Betrieb dieser Anwendung im Produktionsmodus ist ausschliesslich ueber eine gesicherte HTTPS-Verbindung zulaessig.');
    }
}

// 3. Aktiviere Tracy basierend auf der Umgebung
Debugger::enable($appEnv === 'development' ? Debugger::DEVELOPMENT : Debugger::PRODUCTION, __DIR__ . '/../log');

// 4. Erstelle das Request-Objekt
$request = Request::createFromGlobals();

// 5. Erstelle eine Instanz unseres Kernels und übergebe die Abhängigkeit
$kernel = new Kernel($appEnv);

// 6. Übergib die Anfrage an den Kernel und erhalte eine Antwort
$response = $kernel->handleRequest($request);

// 7. Sende die Antwort an den Browser
$response->send();