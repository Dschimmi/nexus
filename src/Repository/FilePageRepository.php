<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

use RuntimeException;

/**
 * Implementierung des PageRepository basierend auf lokalen HTML-Dateien.
 * Speichert Seiten in public/pages/{slug}.html.
 */
class FilePageRepository implements PageRepositoryInterface
{
    private string $storageDir;

    public function __construct(string $projectDir)
    {
        $this->storageDir = $projectDir . '/public/pages';
        
        if (!is_dir($this->storageDir)) {
            if (!mkdir($this->storageDir, 0777, true) && !is_dir($this->storageDir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $this->storageDir));
            }
        }
    }

    public function findBySlug(string $slug): ?array
    {
        $filepath = $this->getFilePath($slug);
        
        if (!file_exists($filepath)) {
            return null;
        }

        $html = file_get_contents($filepath);
        
        // Titel extrahieren (einfacher Regex für Demo-Zwecke)
        preg_match('/<title>(.*?)<\/title>/', $html, $matches);
        $title = $matches[1] ?? ucfirst($slug);
        
        // Body extrahieren
        preg_match('/<body>(.*?)<\/body>/s', $html, $matches);
        $content = $matches[1] ?? $html;

        return [
            'slug' => $slug,
            'title' => $title,
            'content' => $content
        ];
    }

    public function findAll(): array
    {
        $pages = [];
        $files = scandir($this->storageDir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'html') continue;

            $slug = pathinfo($file, PATHINFO_FILENAME);
            $data = $this->findBySlug($slug);
            
            if ($data) {
                $pages[] = $data;
            }
        }
        
        return $pages;
    }

    public function save(string $slug, string $title, string $content): void
    {
        $html = sprintf(
            "<!DOCTYPE html><html><head><title>%s</title></head><body>%s</body></html>",
            htmlspecialchars($title),
            $content // Raw Content erlaubt (Admin)
        );
        
        file_put_contents($this->getFilePath($slug), $html);
    }

    public function delete(string $slug): void
    {
        $filepath = $this->getFilePath($slug);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    private function getFilePath(string $slug): string
    {
        // Einfache Sanitization
        $safeSlug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
        return $this->storageDir . '/' . $safeSlug . '.html';
    }
}