# ADR 018: Security Hardening & Context Awareness (Session & Logging)

**Status:** Akzeptiert  
**Datum:** 2025-12-06  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `SessionService`, `Kernel`, `SecurityLogger`, `AuthenticationService`

## Kontext

Im Rahmen des Sicherheits-Audits (v0.8.10) wurden kritische Lücken in der Session-Sicherheit und dem Logging identifiziert:
1.  **Session Hijacking:** Der Schutz gegen Session-Übernahme war unzureichend (schwaches Fingerprinting).
2.  **Context-Blindheit:** Der Kernel startete Sessions auch für CLI-Befehle, was zu Fehlern und unnötigem Overhead führte.
3.  **Mangelnde Transparenz:** Sicherheitskritische Ereignisse (z. B. Login-Versuche, CSRF-Fehler) wurden nicht strukturiert protokolliert.
4.  **Replay-Angriffe:** Es fehlte ein Mechanismus, um Sessions nach Rechteänderungen sofort ungültig zu machen.

## Entscheidung

Wir implementieren ein umfassendes "Security Hardening" Paket:

### 1. Context-Aware Kernel
Der Kernel prüft nun vor dem Start der Session den Ausführungskontext (`php_sapi_name()`).
*   **Web:** Session wird gestartet (sofern nicht `/api/` Route).
*   **CLI:** Session wird **nicht** gestartet.

### 2. Enhanced Fingerprinting & Anti-Replay
*   **Fingerprint:** Hash aus Browser-Familie (Major Version), anonymisierter IP (IPv4 /24, IPv6 /64) und App-Secret.
*   **Anti-Replay:** Bei jedem Request wird die Integrität des Users (Versionierung) geprüft. Bei Abweichung erfolgt ein Zwangs-Logout.

### 3. Structured Security Logging
Einführung eines `SecurityLogger` Services.
*   **Format:** JSON-Payload mit Event-Typ, Timestamp, anonymisierter IP und Kontext.
*   **Integration:** Wird in `AuthenticationService` und `SessionService` injiziert, um alle sicherheitsrelevanten Vorgänge (Login, CSRF-Fail, Fingerprint-Mismatch) revisionssicher zu protokollieren.

### 4. CSRF-Framework
Implementierung einer tokenbasierten CSRF-Absicherung im `SessionService` (Security Bag) und Bereitstellung via Twig-Funktion `csrf_token()`.

## Konsequenzen

### Positiv
*   **Sicherheit:** Das System erfüllt nun gängige Enterprise-Sicherheitsstandards (OWASP).
*   **Betrieb:** CLI-Tools laufen stabil ohne Session-Fehler.
*   **Audit:** Sicherheitsvorfälle sind durch JSON-Logs automatisiert auswertbar (SIEM-Ready).

### Negativ
*   **Komplexität:** Der `SessionService` und `Kernel` haben an Logik zugenommen.
*   **Stateful:** Die enge Kopplung von Session und Security erschwert (aktuell noch) komplett statenlose API-Designs (wurde aber durch `/api/` Weiche vorbereitet).