# 10. Datei-basierte Administration und Konfiguration (Meilenstein 0.7.0)

Datum: 2025-12-01
Status: Akzeptiert

## Kontext
Für den Meilenstein 0.7.0 "Volle Funktionalität der Standardseiten & Admin-Dashboard" wurde eine Administrationsoberfläche benötigt. Zu diesem Zeitpunkt existiert jedoch noch keine Datenbankanbindung (geplant für v0.8.0+). Zudem mussten Feature-Toggles (z.B. für Cookie-Banner, User-Modul) und statische Inhalte ("Dummy-Seiten") verwaltet werden.

## Entscheidung
Wir haben uns entschieden, für diesen Meilenstein eine **datei-basierte Architektur** zu implementieren, die vollständig entkoppelt von einer Datenbank funktioniert:

1.  **Konfiguration (Feature Toggles):**
    Die Status der Module (User, Suche, etc.) werden in einer JSON-Datei (`config/modules.json`) gespeichert. Ein `ConfigService` liest und schreibt diese Datei.
    *Grund:* Einfache Persistenz, menschenlesbar, kein DB-Schema notwendig.

2.  **Authentifizierung:**
    Der Admin-Zugang erfolgt gegen Environment-Variablen (`ADMIN_USER`, `ADMIN_EMAIL`, `ADMIN_HASH`) in der `.env`-Datei.
    *Grund:* Sicherheit durch Trennung von Code und Credentials, keine Abhängigkeit zu einer noch nicht existierenden User-Tabelle.

3.  **Dummy-Seiten (Static Page Management):**
    Vom Admin erstellte Seiten werden physisch als HTML-Dateien in `/public/pages/` gespeichert. Metadaten (Titel) werden als HTML-Kommentar in der Datei selbst abgelegt.
    *Grund:* Performance (direkter Dateizugriff) und Einfachheit. Der `DynamicPageController` dient als Router für diese Dateien.

4.  **Asset-Strategie (Dev vs. Prod):**
    Der `AssetService` nutzt eine hybride Strategie. In der Produktion liest er das `manifest.json` von Vite. Im Development-Modus (oder wenn kein Build vorhanden ist) fällt er intelligent auf die rohen Dateien in `/public/css` zurück.
    *Grund:* Ermöglicht "Hot-Reloading" und schnelle Entwicklung ohne ständige Build-Prozesse, garantiert aber cache-busting in der Produktion.

## Konsequenzen
*   **Vorteil:** Das System ist extrem leichtgewichtig und portabel. Es kann ohne Datenbank-Setup sofort betrieben werden.
*   **Vorteil:** Hohe Performance bei Dummy-Seiten und Konfigurationsabfragen.
*   **Nachteil:** Die Lösung skaliert nicht für hunderte Benutzer oder komplexe Inhalte.
*   **Migration:** Sobald die Datenbank-Schicht (PostgreSQL/MySQL) eingeführt wird, müssen der `AuthenticationService` und der `PageManagerService` auf DB-Repositories umgestellt werden (Adapter-Pattern). Die Schnittstellen sind jedoch so designed, dass die Controller-Logik weitgehend unberührt bleiben kann.