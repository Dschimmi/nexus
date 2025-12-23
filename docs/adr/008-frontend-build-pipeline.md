# ADR 008: Build-Pipeline mit Vite und Manifest-Integration

* **Status:** Akzeptiert
* **Datum:** 29.11.2025
* **Kontext:** Meilenstein 0.6.0, Erfüllung PH 5.1 und 9.2.2

## Kontext und Problemstellung
Das Framework muss für den Produktivbetrieb optimierte Assets (minifiziert, gebündelt) bereitstellen, um die Performance-Ziele (PH 5.1) zu erreichen. Gleichzeitig soll die Entwicklung ("Dev-Mode") komfortabel sein, ohne dass bei jeder Änderung ein Build-Prozess angestoßen werden muss. PHP (Backend) weiß nativ nichts von den generierten Dateinamen (Cache-Busting Hashes) des Frontend-Builds.

## Entscheidung
Wir führen **Vite** als Build-Tool ein und koppeln es über ein `manifest.json` Pattern an das PHP-Backend.

1.  **Tooling:** Vite wird genutzt, um CSS und JS zu minifizieren und zu versionieren (Hashing).
2.  **Integration:**
    *   Im **Development-Modus** (`APP_ENV=development`) lädt PHP die Dateien direkt über eine manuelle `public/manifest.json`.
    *   Im **Production-Modus** (`APP_ENV=production`) generiert Vite ein `manifest.json` in `public/build/.vite/`.
3.  **PHP-Seitige Logik:**
    *   Ein `AssetService` abstrahiert den Zugriff. Er entscheidet basierend auf der Umgebungsvariable, welches Manifest geladen wird.
    *   Eine Twig-Funktion `{{ asset('logical_name.css') }}` dient als Schnittstelle im Template.

## Konsequenzen
*   **Positiv:** Automatisiertes Cache-Busting, Erfüllung der Minifizierungs-Anforderungen, klare Trennung von Frontend-Build und Backend-Logic.
*   **Negativ:** Einführung einer Node.js-Abhängigkeit (`package.json`) im Projekt-Root.
*   **Sicherheit:** Im Produktionsmodus wird strikt HTTPS erzwungen (PH 5.2), was lokale Tests ohne Zertifikate erschwert (muss durch Self-Signed Certs gelöst werden).