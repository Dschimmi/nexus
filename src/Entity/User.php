<?php

declare(strict_types=1);

namespace MrWo\Nexus\Entity;

/**
 * Repräsentiert einen Benutzer im System.
 * 
 * Diese Klasse dient als zentrales Datentransferobjekt (DTO) zwischen
 * der Persistenzschicht (Repository), der Geschäftslogik (Service) und
 * der Session-Speicherung. Sie ist bewusst anämisch gehalten (keine Logik),
 * um Serialisierung und Transport zu erleichtern.
 */
class User
{
    /**
     * Erstellt eine neue Benutzer-Instanz.
     * 
     * @param string $id           Die eindeutige ID des Benutzers (z.B. UUID oder 'root').
     * @param string $username     Der Anmeldename.
     * @param string $email        Die E-Mail-Adresse.
     * @param string $passwordHash Der gehashte Passwort-String (Argon2id).
     * @param string $group        Die primäre Benutzergruppe (z.B. 'System').
     * @param string $role         Die primäre Rolle (z.B. 'Administrator').
     */
    public function __construct(
        private string $id,
        private string $username,
        private string $email,
        private string $passwordHash,
        private string $group,
        private string $role
    ) {}

    /**
     * Gibt die Benutzer-ID zurück.
     * @return string
     */
    public function getId(): string 
    { 
        return $this->id; 
    }

    /**
     * Gibt den Benutzernamen zurück.
     * @return string
     */
    public function getUsername(): string 
    { 
        return $this->username; 
    }

    /**
     * Gibt die E-Mail-Adresse zurück.
     * @return string
     */
    public function getEmail(): string 
    { 
        return $this->email; 
    }

    /**
     * Gibt den Passwort-Hash zurück.
     * @return string
     */
    public function getPasswordHash(): string 
    { 
        return $this->passwordHash; 
    }

    /**
     * Gibt die Benutzergruppe zurück.
     * @return string
     */
    public function getGroup(): string 
    { 
        return $this->group; 
    }

    /**
     * Gibt die Benutzerrolle zurück.
     * @return string
     */
    public function getRole(): string 
    { 
        return $this->role; 
    }

    /**
     * Konvertiert das Objekt in ein assoziatives Array.
     * Wird verwendet, um Benutzerdaten in der Session zu speichern,
     * ohne das gesamte Objekt (inkl. Passwort-Hash) zu serialisieren.
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'group' => $this->group,
            'role' => $this->role
        ];
    }

    /**
     * Erstellt ein User-Objekt aus einem Array (Factory-Methode).
     * Wird verwendet, um den Benutzer aus den Session-Daten wiederherzustellen.
     * Hinweis: Der Passwort-Hash ist in der Session nicht enthalten und bleibt leer.
     * 
     * @param array $data Das Array mit Benutzerdaten.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            $data['username'] ?? '',
            $data['email'] ?? '',
            '', // Passwort-Hash wird nicht in der Session gespeichert
            $data['group'] ?? '',
            $data['role'] ?? ''
        );
    }
}