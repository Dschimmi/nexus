<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

/**
 * Schnittstelle für den Zugriff auf persistierte Konfigurationen.
 * Entkoppelt den ConfigService vom Speichermedium (File, DB, Redis).
 */
interface ConfigRepositoryInterface
{
    /**
     * Lädt alle gespeicherten Einstellungen.
     * 
     * @return array Assoziatives Array der Einstellungen.
     */
    public function load(): array;

    /**
     * Speichert die Einstellungen.
     * 
     * @param array $settings Das komplette Einstellungs-Array.
     */
    public function save(array $settings): void;
}