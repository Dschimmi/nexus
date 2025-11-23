<?php

namespace MrWo\Nexus\Service;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Kapselt die Logik für die Verwaltung der Benutzersession.
 */
class SessionService
{
    /**
     * @var SessionInterface Das zugrundeliegende Symfony Session-Objekt.
     */
    private SessionInterface $session;

    public function __construct()
    {
        // Erstellt ein neues Session-Objekt
        $this->session = new Session();
    }

    /**
     * Startet die Session, falls sie noch nicht gestartet ist.
     * Muss einmal pro Request aufgerufen werden.
     */
    public function start(): void
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }
    }

    /**
     * Ruft einen Wert aus der Session ab.
     *
     * @param string $key Der Schlüssel des Wertes.
     * @param mixed $default Der Standardwert, falls der Schlüssel nicht existiert.
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->session->get($key, $default);
    }

    /**
     * Speichert einen Wert in der Session.
     *
     * @param string $key Der Schlüssel des Wertes.
     * @param mixed $value Der zu speichernde Wert.
     */
    public function set(string $key, $value): void
    {
        $this->session->set($key, $value);
    }
}