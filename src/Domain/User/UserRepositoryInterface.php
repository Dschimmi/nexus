<?php

declare(strict_types=1);

namespace MrWo\Nexus\Domain\User;

use MrWo\Nexus\Domain\User\User;

/**
 * Schnittstelle für den Zugriff auf Benutzerdaten (Domain-Port).
 * 
 * Dieses Interface ist Teil der Domain-Schicht und definiert den Kontrakt,
 * den die Infrastruktur-Schicht (Repositories) erfüllen muss.
 * Es dient der Entkopplung von Authentifizierungslogik und Datenhaltung
 * gemäß der Hexagonalen Architektur (Dependency Inversion).
 */
interface UserRepositoryInterface
{
    /**
     * Sucht einen Benutzer anhand seines Identifikators (Benutzername oder E-Mail).
     * 
     * @param string $identifier Der Benutzername oder die E-Mail-Adresse.
     * @return User|null Das User-Objekt bei Erfolg, null wenn nicht gefunden.
     */
    public function findByIdentifier(string $identifier): ?User;

    /**
     * Aktualisiert den Passwort-Hash eines Benutzers, wenn die Algorithmus-Parameter veraltet sind.
     * 
     * @param User $user Der betroffene Benutzer.
     * @param string $newHash Der neue, sichere Hash.
     * @return void
     */
    public function upgradePassword(User $user, string $newHash): void;

    /**
     * Speichert oder aktualisiert einen Benutzer.
     * Essenziell für Just-in-Time Provisioning (automatisches Anlegen bei Login)
     * und allgemeine Benutzerverwaltung.
     * 
     * @param User $user Der zu speichernde Benutzer.
     * @return void
     */
    public function save(User $user): void;
}