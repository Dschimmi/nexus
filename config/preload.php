<?php

/**
 * Opcache Preloading Script.
 * Wird in der php.ini via opcache.preload eingebunden.
 * * Lädt rekursiv alle Klassen aus dem src/-Verzeichnis, um
 * verschachtelte Namensräume (z.B. Controller/Api/V1) abzudecken.
 */

$srcDir = __DIR__ . '/../src';

if (!is_dir($srcDir)) {
    return;
}

// 1. Kritische Kern-Klassen manuell vorab laden (Kernel, HomePageController, ConfigService)
if (file_exists($srcDir . '/Kernel/Kernel.php')) {
    opcache_compile_file($srcDir . '/Kernel/Kernel.php');
}

if (file_exists($srcDir . '/Controller/HomepageController.php')) {
    opcache_compile_file($srcDir . '/Controller/HomepageController.php');
}

if (file_exists($srcDir . 'Service/ConfigService.php')) {
    opcache_compile_file($srcDir . 'Service/ConfigService.php');
}

// 2. Rekursives Laden aller Anwendungs-Klassen
// RecursiveDirectoryIterator ist notwendig für Unterordner (z.B. Api/V1)
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        try {
            opcache_compile_file($file->getRealPath());
        } catch (Throwable $e) {
            // Fehler (z.B. Syntaxfehler) loggen, aber Boot-Prozess nicht stoppen
            error_log("Preloading failed for: " . $file->getRealPath() . " - " . $e->getMessage());
        }
    }
}