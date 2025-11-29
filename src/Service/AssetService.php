<?php
namespace MrWo\Nexus\Service;

class AssetService
{
    private array $manifest = [];
    private bool $isProd;

    public function __construct()
    {
        // Wenn APP_ENV nicht gesetzt ist, gehen wir von 'production' aus (Standard XAMPP)
        $env = $_SERVER['APP_ENV'] ?? 'production';
        $this->isProd = $env === 'production';

        // Pfade definieren
        $devManifest = __DIR__ . '/../../public/manifest.json';
        
        // WICHTIG: Vite 5 speichert das Manifest in .vite/manifest.json
        $prodManifest = __DIR__ . '/../../public/build/.vite/manifest.json';

        $manifestFile = $this->isProd ? $prodManifest : $devManifest;

        $this->loadManifest($manifestFile);
    }

    private function loadManifest(string $manifestFile): void
    {
        if (!is_readable($manifestFile)) {
            $this->manifest = [];
            return;
        }

        $json = file_get_contents($manifestFile);
        $decoded = json_decode($json, true);
        $this->manifest = is_array($decoded) ? $decoded : [];
    }

    /**
     * Gibt den Web-Pfad für eine Datei zurück.
     * Nutzt im Prod-Modus das Vite-Manifest.
     */
    public function get(string $logicalName): string
    {
        // 1. Direkter Treffer? (Für manuelles Dev-Manifest)
        if (isset($this->manifest[$logicalName])) {
            return $this->manifest[$logicalName];
        }

        // 2. Vite-Manifest Suche (Prod)
        // Vite nutzt Keys wie "public/css/header.css".
        // Wir suchen, ob ein Key mit unserem logicalName (z.B. "header.css") endet.
        if ($this->isProd) {
            foreach ($this->manifest as $key => $entry) {
                // Prüfen ob der Key mit dem gesuchten Namen endet
                if (str_ends_with($key, $logicalName)) {
                    // Vite liefert 'file' => 'assets/header-xyz.css'.
                    // Wir müssen '/build/' davorhängen.
                    return '/build/' . $entry['file'];
                }
            }
        }

        // 3. Fallback: Wenn nichts gefunden wurde, geben wir den Namen direkt zurück
        // (Hilft im Dev-Modus, falls Manifest fehlt)
        return "/$logicalName";
    }
}