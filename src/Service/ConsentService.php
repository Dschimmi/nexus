<?php

namespace MrWo\Nexus\Service;

/**
 * Verwaltet die Zustimmung des Benutzers zu verschiedenen Cookie-Kategorien.
 * Speichert die Zustimmung persistent im Attribute-Bag der Session.
 */
class ConsentService
{
    private const SESSION_KEY = 'user_consents';

    public function __construct(
        private SessionService $sessionService
    ) {}

    /**
     * Helper: Zugriff auf den Attribute-Bag.
     */
    private function getAttributes(): SessionBag
    {
        return $this->sessionService->getBag('attributes');
    }

    /**
     * Prüft, ob der Benutzer für eine bestimmte Kategorie zugestimmt hat.
     */
    public function hasConsent(string $category): bool
    {
        $consents = $this->getAttributes()->get(self::SESSION_KEY, []);
        return $consents[$category] ?? false;
    }

    /**
     * Erteilt die Zustimmung für eine bestimmte Kategorie.
     */
    public function grantConsent(string $category): void
    {
        $attributes = $this->getAttributes();
        $consents = $attributes->get(self::SESSION_KEY, []);
        
        $consents[$category] = true;
        
        $attributes->set(self::SESSION_KEY, $consents);
    }

    /**
     * Entzieht die Zustimmung für eine bestimmte Kategorie.
     */
    public function revokeConsent(string $category): void
    {
        $attributes = $this->getAttributes();
        $consents = $attributes->get(self::SESSION_KEY, []);
        
        $consents[$category] = false;
        
        $attributes->set(self::SESSION_KEY, $consents);
    }
}