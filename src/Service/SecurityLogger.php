<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use Tracy\Debugger;

/**
 * Zentraler Logger für sicherheitsrelevante Ereignisse.
 * Formatiert Logs als JSON und anonymisiert IP-Adressen (DSGVO).
 */
class SecurityLogger
{
    /**
     * Protokolliert ein Ereignis.
     */
    public function log(string $event, array $context = []): void
    {
        $payload = [
            'event' => $event,
            'timestamp' => date('c'),
            'ip' => $this->anonymizeIp($_SERVER['REMOTE_ADDR'] ?? ''),
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            // Session ID Hash (optional, falls Session aktiv)
            'session_id_hash' => (session_status() === PHP_SESSION_ACTIVE) ? hash('sha256', session_id()) : null,
            'context' => $context
        ];

        Debugger::log('[SECURITY] ' . json_encode($payload), Debugger::INFO);
    }

    /**
     * Anonymisiert IPv4 und IPv6 Adressen.
     */
    public function anonymizeIp(string $ip): string
    {
        // IPv4 (/24)
        if (strpos($ip, '.') !== false) {
            $parts = explode('.', $ip);
            return count($parts) === 4 ? implode('.', array_slice($parts, 0, 3)) . '.0' : $ip;
        }

        // IPv6 (/64)
        if (strpos($ip, ':') !== false) {
            $packed = inet_pton($ip);
            if ($packed === false) return $ip;
            $mask = str_repeat("\xFF", 8) . str_repeat("\x00", 8);
            return inet_ntop($packed & $mask);
        }

        return $ip;
    }
}