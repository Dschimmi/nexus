<?php

declare(strict_types=1);

namespace MrWo\Nexus\Domain\Page;

/**
 * Schnittstelle für den Zugriff auf Inhaltsseiten (Domain Port).
 * 
 * Teil der Domain-Schicht. Definiert, wie Seiten geladen/gespeichert werden,
 * unabhängig von der technischen Implementierung (File/DB).
 */
interface PageRepositoryInterface
{
    /**
     * Findet eine Seite anhand ihres Slugs.
     * @return array|null Seitendaten (title, content) oder null.
     */
    public function findBySlug(string $slug): ?array;

    /**
     * Gibt alle verfügbaren Seiten zurück.
     * @return array Liste von Seiten (slug, title).
     */
    public function findAll(): array;

    /**
     * Speichert eine Seite.
     */
    public function save(string $slug, string $title, string $content): void;

    /**
     * Löscht eine Seite.
     */
    public function delete(string $slug): void;
}