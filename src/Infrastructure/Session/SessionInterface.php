<?php

declare(strict_types=1);

namespace MrWo\Nexus\Infrastructure\Session;

use MrWo\Nexus\Infrastructure\Session\SessionBag;

/**
 * Definiert den Port (Interface) für den Zugriff und die Verwaltung von Session-Daten.
 * Entkoppelt die Anwendung von der konkreten Implementierung (Session-Adapter).
 */
interface SessionInterface
{
    /**
     * Startet die PHP-Session, falls diese noch nicht aktiv ist.
     * Initialisiert alle registrierten Session Bags mit den gespeicherten Daten.
     */
    public function start(): void;

    /**
     * Gibt einen Session Bag (Container) anhand seines Namens zurück.
     * Ein Bag wird erstellt, falls er noch nicht registriert wurde.
     *
     * @param string $name Der Name des Session Bags (z.B. 'security', 'flashes').
     * @return SessionBag
     */
    public function getBag(string $name): SessionBag;

    /**
     * Speichert die aktuellen Session-Daten in den Session-Handler und schließt die Session.
     * Dies sollte am Ende jeder Anfrage aufgerufen werden.
     */
    public function save(): void;

    /**
     * Migriert die aktuelle Session-ID und regeneriert den Session-Cookie.
     * Wird typischerweise nach einer erfolgreichen Authentifizierung aufgerufen (Session Fixation Schutz).
     *
     * @param bool $destroy Wenn true, werden die alten Session-Daten zerstört.
     */
    public function migrate(bool $destroy = false): void;

    /**
     * Macht die Session ungültig, löscht alle Daten, zerstört den Cookie und beendet die Session auf dem Server.
     */
    public function invalidate(): void;

    /**
     * Fügt eine Flash-Nachricht zur Session hinzu, die nur für die nächste Anfrage verfügbar ist.
     *
     * @param string $type Der Typ der Nachricht (z.B. 'success', 'error').
     * @param string $message Der Nachrichtentext.
     */
    public function addFlash(string $type, string $message): void;

    /**
     * Gibt alle Flash-Nachrichten zurück und entfernt diese anschließend aus der Session.
     *
     * @return array Ein assoziatives Array von Flash-Nachrichten, gruppiert nach Typ.
     */
    public function getFlashes(): array;

    /**
     * Generiert einen kryptografisch sicheren CSRF-Token und speichert ihn in einem Session Bag.
     *
     * @param string $tokenId Eine eindeutige ID, die den Zweck des Tokens identifiziert (z.B. 'login_form').
     * @return string Der generierte Token-String.
     */
    public function generateCsrfToken(string $tokenId): string;

    /**
     * Validiert einen übermittelten Token gegen den in der Session gespeicherten Token.
     * Der gespeicherte Token wird nach erfolgreicher Validierung aus der Session entfernt (One-Time Token).
     *
     * @param string $tokenId Die ID des Tokens.
     * @param string|null $token Der vom Client übermittelte Token.
     * @return bool True, wenn die Tokens übereinstimmen (unter Verwendung von hash_equals).
     */
    public function isCsrfTokenValid(string $tokenId, ?string $token): bool;
}