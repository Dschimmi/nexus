<?php

namespace MrWo\Nexus\Service;

/**
     * Verwaltet die Zustimmung des Benutzers zu verschiedenen Cookie-Kategorien.
     * Speichert die Zustimmung persistent in der Session.
     */
class ConsentService
{
    /**
     * Der Schlüssel, unter dem die Zustimmungen in der Session gespeichert werden.
     */
    private const SESSION_KEY = 'user_consents';
    /**
     * @var SessionService Der Session-Service zur Speicherung der Daten.
     */
    private SessionService $sessionService;

    /**
     * @param SessionService $sessionService Der zu injizierende Session-Service.
     */
    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Prüft, ob der Benutzer für eine bestimmte Kategorie zugestimmt hat.
     */
    public function hasConsent(string $category): bool
    {
        $consents = $this->sessionService->get(self::SESSION_KEY, []);
        return $consents[$category] ?? false;
    }

    /**
     * Erteilt die Zustimmung für eine bestimmte Kategorie.
     */
    public function grantConsent(string $category): void
    {
        $consents = $this->sessionService->get(self::SESSION_KEY, []);
        $consents[$category] = true;
        $this->sessionService->set(self::SESSION_KEY, $consents);
    }

    /**
     * Entzieht die Zustimmung für eine bestimmte Kategorie.
     */
    public function revokeConsent(string $category): void
    {
        $consents = $this->sessionService->get(self::SESSION_KEY, []);
        $consents[$category] = false;
        $this->sessionService->set(self::SESSION_KEY, $consents);
    }
}