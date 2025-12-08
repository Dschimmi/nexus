<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

use MrWo\Nexus\Entity\User;

/**
 * Schnittstelle für den Zugriff auf Benutzerdaten.
 * 
 * Definiert die Methoden, die jede Benutzerquelle (Datenbank, Datei, LDAP)
 * bereitstellen muss, um vom AuthenticationService genutzt werden zu können.
 * Dient der Entkopplung von Authentifizierungslogik und Datenhaltung.
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
}