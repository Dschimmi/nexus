# Nexus Enterprise Framework

Dieses Dokument dient als zentraler Einstiegspunkt für Entwickler und Administratoren des Nexus-Frameworks. Es beschreibt die grundlegende Installation, Konfiguration und den Betrieb der Anwendung.

---

## 1. Projekt-Übersicht

Nexus ist ein modulares, hochperformantes Web-Framework, das auf der **Hexagonalen Architektur** basiert. Es legt höchsten Wert auf Sicherheit (Argon2id, CSRF-Schutz, Session-Hardening) und Skalierbarkeit (Stateless Architecture).

## 2. Systemanforderungen

Um eine reibungslose Ausführung zu gewährleisten, muss die Umgebung folgende Anforderungen erfüllen:
*   **PHP-Version:** 8.2 oder höher (strikte Typisierung erforderlich).
*   **Datenbank:** PDO-kompatible Schnittstelle (MySQL/PostgreSQL empfohlen).
*   **Webserver:** Apache (mit mod_rewrite) oder Nginx.
*   **Zusatz-Tools:** 
    *   Composer 2.x (Abhängigkeitsmanagement).
    *   Node.js & NPM (für den Asset-Build-Prozess).
    *   Xdebug (optional, für Code-Coverage Messungen).

## 3. Installation und Setup

Befolgen Sie diese Schritte, um eine lokale Entwicklungsumgebung aufzusetzen:

1.  **Abhängigkeiten installieren:**
    Führen Sie im Projekt-Root aus:
    `composer install`
    `npm install`

2.  **Umgebung konfigurieren:**
    Kopieren Sie die Vorlage `.env.example` nach `.env` und passen Sie die Werte an.
    *Wichtig:* Generieren Sie ein sicheres `APP_SECRET` und setzen Sie den `ADMIN_PASSWORD_HASH`.

3.  **Frontend-Assets bauen:**
    Verwenden Sie Vite, um CSS und JavaScript zu bündeln:
    `npm run build`

## 4. Betrieb und Entwicklung

### Lokaler Server
Starten Sie den PHP-eigenen Server für schnelle Tests:
`php -S localhost:8000 -t public`

### Statische Analyse (Qualitätssicherung)
Prüfen Sie den Code auf Typsicherheit und Standards:
`vendor/bin/php-cs-fixer fix --dry-run --diff`
`vendor/bin/phpstan analyse`

### Automatisierte Tests
Führen Sie die Unit- und Integrationstests aus:
`vendor/bin/phpunit`

## 5. Dokumentation

Weiterführende Informationen finden Sie im Verzeichnis `/docs`:
*   `/docs/architecture/`: Enthält alle Architecture Decision Records (ADRs).
*   `/docs/development.md`: Handbuch für die Entwicklung von Modulen.
*   `/docs/requirements/`: Das vollständige Pflichtenheft.