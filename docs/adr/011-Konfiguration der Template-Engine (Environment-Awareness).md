# 11. Konfiguration der Template-Engine (Environment-Awareness)

* **Status:** Angenommen
* **Datum:** 2025-12-01
* **Bezieht sich auf:** [ADR 002: Wahl der Kern-Bibliotheken](002-wahl-der-kern-bibliotheken.md), [ADR 006: Environments](006-environments.md)

## Kontext

Das System verwendet gemäß ADR 002 **Twig** als Template-Engine. Die Standardkonfiguration von Twig ist jedoch generisch und unterscheidet nicht zwischen Entwicklungs- und Produktivumgebung.

Das Pflichtenheft stellt konkrete Anforderungen an das System, die sich widersprechen, wenn man nur eine statische Konfiguration verwendet:
1.  **Performance (Produktion):** Das System muss Antwortzeiten (TTFB) von unter 500ms gewährleisten und Caching-Mechanismen nutzen (PH: 5.1).
2.  **Sicherheit (Produktion):** Der Schutz vor XSS durch Auto-Escaping ist verbindlich vorgeschrieben (PH: 5.2). Zudem dürfen keine Debug-Informationen im Live-Betrieb nach außen dringen (PH: 9.1.1.9.3).
3.  **Code-Qualität (Entwicklung):** Es gilt eine "Fail-Fast-Strategie" (PH: 9.1.1.1), um Fehler frühzeitig zu erkennen.

## Entscheidung

Wir konfigurieren den `Twig\Environment`-Service dynamisch im Dependency Injection Container, basierend auf der Umgebungsvariable `APP_ENV` (siehe ADR 006).

Die Konfiguration wird wie folgt festgelegt:

### 1. Im Modus `production`
* **Cache:** Aktiviert (`/var/cache/twig`).
    * *Begründung:* Erfüllt die Leistungsanforderungen (PH: 5.1), da Templates nicht bei jedem Request neu kompiliert werden müssen.
* **Debug:** Deaktiviert.
    * *Begründung:* Verhindert `Information Disclosure` und erfüllt die Sicherheitsvorgaben.
* **Strict Variables:** Deaktiviert.
    * *Begründung:* Erhöht die Robustheit im Live-Betrieb (Rendering bricht nicht ab, wenn eine unkritische Variable fehlt).
* **Auto-Reload:** Deaktiviert.
    * *Begründung:* Performance-Optimierung (keine Prüfung auf Dateiänderungen).

### 2. Im Modus `development`
* **Cache:** Deaktiviert (`false`).
    * *Begründung:* Ermöglicht "Hot Reloading"; Änderungen an Templates sind sofort sichtbar (Developer Experience).
* **Debug:** Aktiviert.
    * *Begründung:* Ermöglicht die Nutzung der `dump()`-Funktion für Fehleranalysen.
* **Strict Variables:** Aktiviert.
    * *Begründung:* Erzwingt sauberen Code ("Fail-Fast"). Der Zugriff auf nicht definierte Variablen wirft sofort einen Fehler, was versteckte Bugs verhindert.
* **Auto-Reload:** Aktiviert.

## Konsequenzen

### Positiv
* **Compliance:** Wir erfüllen explizit die Anforderungen an **Performance** (PH: 5.1) und **Sicherheit** (PH: 5.2), insbesondere den dort geforderten XSS-Schutz durch Auto-Escaping.
* **Stabilität:** Durch `strict_variables` in der Entwicklung werden Tippfehler in Variablennamen sofort erkannt, bevor sie in die Produktion gelangen.
* **Effizienz:** Entwickler müssen den Cache nicht manuell leeren, um Änderungen zu sehen.

### Negativ / Risiken
* **Deployment-Komplexität:** Der Deployment-Prozess muss sicherstellen, dass der Ordner `var/cache/` existiert und vom Webserver beschreibbar ist (Schreibrechte), da Twig sonst im Produktivmodus eine Exception wirft.