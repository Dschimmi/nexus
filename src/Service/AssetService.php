<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

/**
 * Zentraler Service für das Asset-Management.
 * 
 * Dieser Service ist dafür verantwortlich, den korrekten öffentlichen Pfad zu
 * CSS- und JavaScript-Dateien zu ermitteln. Er unterscheidet dabei intelligent
 * zwischen:
 * 1. Produktions-Modus: Nutzung von minifizierten, versionierten Dateien aus dem Vite-Manifest.
 * 2. Entwicklungs-Modus: Direkter Zugriff auf die Quelldateien oder Fallback-Logik.
 */
class AssetService
{
    /**
     * @var array Enthält das geladene Manifest (Mapping von logischen Namen zu Dateipfaden).
     */
    private array $manifest = [];

    /**
     * @var bool Status-Flag: True wenn Produktionsumgebung, sonst False.
     */
    private bool $isProd;

    /**
     * Initialisiert den Service und lädt das passende Manifest.
     * Erkennt automatisch die Umgebung (DEV/PROD).
     */
    public function __construct()
    {
        // 1. Umgebung ermitteln
        // Wir prüfen zuerst $_ENV (phpdotenv), dann $_SERVER, Fallback ist 'production'.
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';
        $this->isProd = $env === 'production';

        // 2. Pfade zu den Manifest-Dateien definieren
        
        // Pfad für manuelles Dev-Manifest (optional)
        $devManifest = __DIR__ . '/../../public/manifest.json';
        
        // Pfad für generiertes Vite-Manifest (Standard für Produktion)
        // WICHTIG: Vite 5 speichert das Manifest standardmäßig in .vite/manifest.json
        $prodManifest = __DIR__ . '/../../public/build/.vite/manifest.json';

        // 3. Manifest basierend auf Umgebung auswählen
        $manifestFile = $this->isProd ? $prodManifest : $devManifest;

        $this->loadManifest($manifestFile);
    }

    /**
     * Lädt und parst die Manifest-Datei sicher.
     * Setzt das Manifest auf ein leeres Array, falls die Datei nicht lesbar ist.
     *
     * @param string $manifestFile Der absolute Pfad zur JSON-Datei.
     * @return void
     */
    private function loadManifest(string $manifestFile): void
    {
        if (!is_readable($manifestFile)) {
            $this->manifest = [];
            return;
        }

        $json = file_get_contents($manifestFile);
        $decoded = json_decode($json, true);
        
        // Sicherstellen, dass wir immer ein Array haben
        $this->manifest = is_array($decoded) ? $decoded : [];
    }

    /**
     * Ermittelt den öffentlichen Web-Pfad für eine Asset-Datei.
     * 
     * Implementiert eine 3-stufige Auflösungsstrategie:
     * 1. Direkter Treffer im Manifest.
     * 2. Unscharfe Suche im Vite-Manifest (für Produktions-Builds).
     * 3. "Smart Fallback" für lokale Entwicklung ohne Build-Prozess.
     *
     * @param string $logicalName Der logische Name der Datei (z.B. 'header.css').
     * @return string Der absolute Pfad für den Browser (z.B. '/css/header.css' oder '/build/assets/header-AH7s.css').
     */
    public function get(string $logicalName): string
    {
        // STRATEGIE 1: Direkter Treffer
        // Nützlich für manuell gepflegte Manifeste im Dev-Modus.
        if (isset($this->manifest[$logicalName])) {
            return $this->manifest[$logicalName];
        }

        // STRATEGIE 2: Vite-Manifest Suche (Production)
        // Vite verwendet Keys wie "public/css/header.css" oder "css/header.css".
        // Wir suchen einen Eintrag, der mit unserem logischen Namen endet.
        if ($this->isProd) {
            foreach ($this->manifest as $key => $entry) {
                // Prüfen ob der Manifest-Key mit dem gesuchten Namen endet (z.B. 'header.css')
                if (str_ends_with($key, $logicalName)) {
                    // Vite liefert relative Pfade wie 'file' => 'assets/header-xyz.css'.
                    // Wir müssen den Build-Ordner '/build/' davorhängen.
                    return '/build/' . $entry['file'];
                }
            }
        }

        // STRATEGIE 3: Smart Fallback (Development)
        // Wenn kein Manifest existiert (z.B. weil /build gelöscht wurde),
        // versuchen wir, den Pfad basierend auf Konventionen zu erraten.
        // Dies ermöglicht "Hot-Reloading" durch einfaches Bearbeiten der Dateien in /public/css/.
        
        // Fallback für CSS: 'header.css' -> '/css/header.css'
        if (str_ends_with($logicalName, '.css') && !str_starts_with($logicalName, 'css/')) {
            return "/css/$logicalName";
        }

        // Fallback für JS: 'app.js' -> '/js/app.js'
        if (str_ends_with($logicalName, '.js') && !str_starts_with($logicalName, 'js/')) {
            return "/js/$logicalName";
        }

        // Letzter Ausweg: Den Namen unverändert zurückgeben (z.B. '/images/logo.png')
        return "/$logicalName";
    }
}