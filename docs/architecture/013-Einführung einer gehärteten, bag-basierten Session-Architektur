# ADR 013: Einführung einer gehärteten, bag-basierten Session-Architektur

**Status:** Akzeptiert  
**Datum:** 2025-12-05  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `SessionService`, `Kernel`, `AuthenticationService`, `ConsentService`

## Kontext

Die ursprüngliche Implementierung des Session-Managements im Nexus-Framework basierte auf einem einfachen Wrapper um die globale `$_SESSION`-Variable. Im Zuge der Spezifikation für die Module "Userverwaltung" (PH 4.2.1) und "Terminal-Emulation" (PHWM) wurden fundamentale architektonische Defizite identifiziert, die eine sichere und stabile Skalierung verhinderten:

1.  **Fehlende Daten-Isolation:** Alle Module schrieben in einen flachen Namensraum. Dies führte zu einem hohen Risiko von Datenkollisionen (z. B. überschreibt ein Shop-Modul die `user_id` des Admin-Panels).
2.  **Race Conditions:** Das Fehlen von Locking-Mechanismen führte bei parallelen Anfragen (z. B. AJAX, Type-Ahead) zu Datenverlusten durch das "Last Write Wins"-Prinzip.
3.  **Sicherheitslücken:** Essenzielle Mechanismen wie Security-Versioning (zur globalen Session-Invalidierung), Anti-Replay-Schutz und Fingerprinting waren nicht oder nur rudimentär vorhanden. Zudem barg die Nutzung von PHPs nativem `serialize()` Risiken für Object Injection Angriffe.

## Entscheidung

Wir ersetzen das bestehende Session-Management vollständig durch eine **Service-orientierte Architektur (SessionService 2.0)**, die folgende Prinzipien erzwingt:

### 1. Attribute Bags (Container-Modell)
Die Session wird logisch in isolierte Bereiche ("Bags") unterteilt. Ein direkter Schreibzugriff auf die Session-Wurzel ist untersagt.
*   **Security Bag:** Für Authentifizierungsdaten (User ID, Rollen).
*   **Attribute Bag:** Für allgemeine Anwendungsdaten.
*   **Flash Bag:** Für flüchtige Nachrichten (Auto-Expire).

### 2. Explicit Save & Lifecycle
Das Speichern der Session-Daten (`save()`) erfolgt nicht mehr implizit durch PHP am Skriptende, sondern wird explizit durch den `Kernel` gesteuert. Dies ermöglicht atomare Schreiboperationen und verhindert Locking-Probleme bei langlaufenden Skripten.

### 3. Sicherheits-Härtung
*   **Fingerprinting:** Beim Start wird ein Hash aus Browser-Familie (Major Version) und anonymisierter IP-Adresse gebildet und bei jedem Request validiert.
*   **JSON-Serialisierung:** Die Speicherung erfolgt ausschließlich im JSON-Format, um die Ausführung von Schadcode bei der Deserialisierung zu verhindern.
*   **Migration:** Die Methode `migrate()` (Session ID Rotation) ist bei jedem Privilegienwechsel verpflichtend.

## Konsequenzen

### Positiv
*   **Datenintegrität:** Kollisionen zwischen Modulen sind ausgeschlossen. Race Conditions werden durch Locking (bei Redis) oder File-Locks verhindert.
*   **Sicherheit:** Das System entspricht nun Enterprise-Sicherheitsstandards (OWASP Session Management Cheatsheet).
*   **Zukunftssicherheit:** Der Speicherort (Storage Handler) ist austauschbar (Redis, DB), ohne den Anwendungscode zu ändern.

### Negativ
*   **Breaking Change:** Sämtlicher bestehender Code, der auf den alten `SessionService` zugriff, musste refactored werden (`$session->getBag(...)->set(...)`).
*   **Komplexität:** Der Initialisierungsaufwand für Entwickler steigt leicht, da sie sich für den korrekten "Bag" entscheiden müssen.