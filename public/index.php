<?php

require_once __DIR__ . '/../vendor/autoload.php';

// 1. Lade die .env Datei
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use MrWo\Nexus\Kernel\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Tracy\Debugger;

// 2. Hole die Environment-Variable EINMALIG (Fail-Safe)
$appEnv = $_SERVER['APP_ENV'] ?? 'production';

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