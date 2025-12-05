<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

/**
 * Repräsentiert einen isolierten Daten-Container ("Bag") innerhalb der Session.
 * 
 * Dient der logischen Trennung von Daten verschiedener Module (z.B. 'security', 'attributes', 'flash'),
 * um Namenskonflikte bei Schlüsseln zu verhindern. Änderungen an diesem Objekt
 * werden erst beim Aufruf von SessionService::save() persistiert.
 */
class SessionBag
{
    /**
     * @param string $name Der Name des Bags (Namespace).
     * @param array  $data Die initialen Daten aus dem Session-Speicher.
     */
    public function __construct(
        private string $name,
        private array $data = []
    ) {}

    /**
     * Setzt einen Wert für einen bestimmten Schlüssel.
     * Überschreibt vorhandene Werte.
     * 
     * @param string $key   Der Schlüssel.
     * @param mixed  $value Der zu speichernde Wert.
     */
    public function set(string $key, mixed $value): void 
    { 
        $this->data[$key] = $value; 
    }

    /**
     * Ruft einen Wert ab.
     * 
     * @param string $key     Der Schlüssel.
     * @param mixed  $default Rückgabewert, falls der Schlüssel nicht existiert.
     * @return mixed Der gespeicherte Wert oder der Default.
     */
    public function get(string $key, mixed $default = null): mixed 
    { 
        return $this->data[$key] ?? $default; 
    }

    /**
     * Prüft, ob ein Schlüssel existiert.
     * 
     * @param string $key Der Schlüssel.
     * @return bool True, wenn vorhanden.
     */
    public function has(string $key): bool 
    { 
        return isset($this->data[$key]); 
    }

    /**
     * Entfernt einen Schlüssel aus dem Bag.
     * 
     * @param string $key Der zu entfernende Schlüssel.
     */
    public function remove(string $key): void 
    { 
        unset($this->data[$key]); 
    }

    /**
     * Gibt alle Daten des Bags als Array zurück.
     * Wird für die Serialisierung benötigt.
     * 
     * @return array
     */
    public function all(): array 
    { 
        return $this->data; 
    }

    /**
     * Leert den gesamten Bag.
     */
    public function clear(): void 
    { 
        $this->data = []; 
    }

    /**
     * Fügt einen Wert zu einem Array unter dem angegebenen Schlüssel hinzu.
     * Nützlich für Flash-Messages oder Listen.
     * Initialisiert den Schlüssel als Array, falls er noch nicht existiert.
     * 
     * @param string $key   Der Schlüssel.
     * @param mixed  $value Der hinzuzufügende Wert.
     */
    public function add(string $key, mixed $value): void 
    { 
        if (!isset($this->data[$key]) || !is_array($this->data[$key])) {
            $this->data[$key] = [];
        }
        $this->data[$key][] = $value; 
    }
}