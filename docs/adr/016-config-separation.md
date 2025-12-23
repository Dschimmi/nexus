# ADR 016: Trennung von Konfiguration und Code (Config & Code Separation)

**Status:** Akzeptiert  
**Datum:** 2025-12-05  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `ConfigService`, `SessionService`, `.env`

## Kontext

Im ursprünglichen Design des `ConfigService` waren Standardwerte (Defaults) für kritische Infrastruktur-Parameter (wie `session.lifetime` oder `app.secret`) hardcodiert im PHP-Code hinterlegt. Dies verstößt gegen das **12-Factor App Prinzip** ("Store config in the environment") und führte zu Sicherheitsrisiken (Secrets im Code) sowie mangelnder Flexibilität bei Deployments.

## Entscheidung

Wir stellen das Konfigurations-Management auf eine strikte Trennung um:

### 1. Environment-First Strategie
Der `ConfigService` lädt seine Default-Werte nicht mehr aus statischen Arrays, sondern primär aus den Umgebungsvariablen (`$_ENV`).
*   **Secrets:** Werte wie `APP_SECRET` müssen zwingend in der `.env` Datei definiert sein.
*   **Infrastruktur:** Werte wie `SESSION_LIFETIME` werden ebenfalls aus der Umgebung geladen.

### 2. Dependency Injection für Konfiguration
Services (wie der `SessionService`) dürfen keine eigenen Konfigurationsdateien parsen oder Konstanten definieren. Sie müssen den `ConfigService` injiziert bekommen und ihre Parameter über `$config->get('key')` beziehen.

### 3. Namens-Neutralität (White-Labeling)
Der Anwendungsname ("Nexus" / "Exelor") ist nicht mehr im Code verankert, sondern wird über `APP_NAME` konfiguriert. Dies ermöglicht den Einsatz des Frameworks als White-Label-Lösung.

## Konsequenzen

### Positiv
*   **Sicherheit:** Secrets (Salts, Keys) sind nicht mehr Teil des Quellcodes (Git), sondern liegen sicher auf dem Server (.env).
*   **Flexibilität:** Infrastruktur-Parameter können pro Umgebung (Dev, Staging, Prod) geändert werden, ohne den Code neu zu bauen.
*   **Testbarkeit:** Tests können Konfigurationen einfach durch Mocking des `ConfigService` simulieren.

### Negativ
*   **Setup-Aufwand:** Die `.env` Datei wird komplexer und muss bei der Installation korrekt befüllt werden (dokumentiert in `.env.example`).