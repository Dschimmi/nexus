# ADR 028: Security Architecture & Scalability Foundation

**Status:** Akzeptiert  
**Datum:** 2025-12-06  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `Kernel`, `AuthenticationService`, `QueueInterface`

## Kontext

Für den produktiven Einsatz und die Skalierung (Cluster/Cloud) fehlten dem Framework essenzielle Sicherheitsmechanismen und Abstraktionen:
1.  **Zugriffskontrolle:** Controller waren standardmäßig öffentlich ("Allow by Default").
2.  **Credentials:** Veraltete Hash-Verfahren konnten nicht automatisch aktualisiert werden.
3.  **Skalierbarkeit:** Tightly coupled Dateioperationen und synchrone Verarbeitung verhinderten horizontale Skalierung.

## Entscheidung

### 1. Deny by Default (Firewall)
Wir implementieren eine Kernel-Level Firewall.
*   **Logik:** Jeder Request erfordert Authentifizierung, es sei denn, der Controller/Methode ist explizit mit dem Attribut `#[IsPublic]` markiert.
*   **Vorteil:** Maximale Sicherheit. Vergessene Konfiguration führt zu "Access Denied", nicht zu Datenlecks.

### 2. Modernes Hashing & Rehash
*   **Algorithmus:** `Argon2id` ist verbindlich.
*   **Migration:** Bei jedem Login prüft das System (`password_needs_rehash`), ob der Hash veraltet ist, und aktualisiert ihn transparent. `UserRepositoryInterface` wurde um `upgradePassword()` erweitert.

### 3. Asynchrone Verarbeitung (Queues)
Einführung von `QueueInterface` in der Domain-Schicht.
*   **Entkopplung:** Applikationslogik sendet Nachrichten ("Job"), statt Arbeit sofort zu erledigen.
*   **Implementierung:** Default ist `SyncQueue` (sofort). Austauschbar gegen RabbitMQ/Redis ohne Code-Änderung.

## Konsequenzen

### Positiv
*   **Sicherheit:** Das Framework ist "Secure by Default".
*   **Performance:** Langlaufende Aufgaben können ausgelagert werden.
*   **Skalierbarkeit:** Der Weg für Stateless-Deployments ist geebnet.

### Negativ
*   **Entwicklung:** Entwickler müssen daran denken, öffentliche Routen explizit freizuschalten (`#[IsPublic]`).