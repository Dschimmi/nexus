<?php
namespace MrWo\Nexus\Service;

class AssetService
{
    private array $manifest = [];

    /**
     * Konstruktor lädt automatisch das passende Manifest
     * abhängig von APP_ENV.
     */
    public function __construct()
    {
        $env = $_SERVER['APP_ENV'] ?? 'production';

        // Dev: rohes Manifest
        $devManifest = __DIR__ . '/../../public/manifest.json';

        // Prod: gebündeltes/Hash-Manifest
        $prodManifest = __DIR__ . '/../../public/build/manifest.json';

        $manifestFile = $env === 'production' ? $prodManifest : $devManifest;

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
     * Gibt den Pfad für einen logischen Asset-Namen zurück.
     * Fallback: logischer Name selbst.
     */
    public function get(string $logicalName): string
    {
        return $this->manifest[$logicalName] ?? "/$logicalName";
    }
}
