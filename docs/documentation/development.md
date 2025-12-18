# Nexus Framework - Entwicklerdokumentation

Willkommen im Entwickler-Handbuch für das Projekt **Nexus** (Codename: Exelor).
Dieses Dokument dient als zentrale Referenz für die Architektur, Installation und Weiterentwicklung des Frameworks.

---

### 1. Installation und Inbetriebnahme

### 1.1 Systemvoraussetzungen
Um Nexus lokal zu entwickeln oder zu betreiben, muss die Umgebung folgende Voraussetzungen erfüllen:

*   **PHP:** Version **8.2** oder höher.
*   **Erweiterungen:** `mbstring`, `intl`, `pdo`, `json`, `opcache`.
*   **Composer:** Version **2.x**.
*   **Node.js & NPM:** Aktuelle LTS-Version (für den Frontend-Build mit Vite).
*   **Webserver:** Apache 2.4+ (mit `mod_rewrite`) oder Nginx.
*   **Datenbank:** Optional für den Core, aber empfohlen (MySQL/PostgreSQL) für Module wie Userverwaltung.

### 1.2 Schritt-für-Schritt Installation

**1. Repository klonen**
git clone https://github.com/Dschimmi/nexus.git
cd nexus

**2. Abhängigkeiten installieren**
Wir nutzen Composer für das Backend und NPM für das Frontend.
composer install
npm install

**3. Frontend bauen**
Erstellt die CSS/JS-Bundles für die Produktion.
npm run build

**4. Umgebungskonfiguration (.env)**
Kopiere die Vorlage `.env.example` nach `.env` und passe sie an.

Wichtige Einstellungen:
*   `APP_ENV=development`
*   `APP_SECRET`: Ein zufälliger String für Session-Sicherheit.
*   `ADMIN_PASSWORD_HASH`: Generiert mit `php -r "echo password_hash('Passwort', PASSWORD_ARGON2ID);"`

**5. Dateisystem-Berechtigungen**
Stelle sicher, dass der Webserver Schreibrechte auf folgende Verzeichnisse hat:
*   `var/` (Logs und Cache)
*   `public/pages/` (Für generierte Dummy-Seiten)
*   `config/modules.json` (Für Feature-Toggles)
*   `public/sitemap.xml`

### 1.3 Webserver Konfiguration

Das Document Root muss auf das Verzeichnis **`/public`** zeigen.

**Apache (.htaccess)**
Eine `.htaccess` Datei liegt bereits in `/public` und leitet alle Anfragen, die keine Dateien sind, auf die `index.php` um. Stelle sicher, dass `AllowOverride All` in deiner VHost-Konfiguration aktiv ist.

**Nginx (Beispielkonfiguration)**

server {
    server_name nexus.local;
    root /path/to/nexus/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }
}

---

## 2. Architektur-Überblick

Das Framework folgt den Prinzipien der **Hexagonalen Architektur** (auch bekannt als Ports & Adapters). Ziel ist die strikte Trennung von Geschäftslogik (Core) und technischer Infrastruktur (Web, Datenbank, Dateisystem). Dies ermöglicht eine hohe Wartbarkeit und Testbarkeit.

### 2.1 Die Schichten (Layers)

1.  **Domain (Der Kern):**
    *   Enthält die Geschäftslogik, Entitäten (`User`) und Interfaces (`UserRepositoryInterface`).
    *   **Regel:** Darf keine Abhängigkeiten nach außen haben (kein `Symfony\`, kein `PDO`).

2.  **Application (Die Anwendungsfälle):**
    *   Enthält Services (`AuthenticationService`, `PageManager`), die den Ablauf steuern.
    *   Nutzt Domain-Objekte und Interfaces.

3.  **Infrastructure (Die Adapter):**
    *   Implementiert die Interfaces der Domain (`EnvUserRepository`, `FilePageRepository`).
    *   Stellt technische Dienste bereit (`SessionService`, `ConfigService`, `DatabaseService`).
    *   Hier finden Datenbankzugriffe, Dateisystemoperationen und API-Calls statt.

4.  **Presentation (Der Eingang):**
    *   Nimmt Anfragen entgegen und gibt Antworten zurück.
    *   Enthält `Controller` (Web & API) und `Command` (CLI).

**4. Infrastructure Layer**
Die technische Basis. In der aktuellen Version (v0.x) besteht diese primär aus:
*   Dateisystem-Zugriffen (Json für Config, Html für Content).
*   Der Konfiguration in `config/`.
*   Den externen Bibliotheken (`vendor/`).

---

### 2.2 Dependency Injection (DI)

Wir nutzen den **Symfony DependencyInjection** Component als Container.

*   **Explizite Konfiguration:** Es wird *kein* Autowiring verwendet. Alle Services und ihre Abhängigkeiten müssen explizit in `config/services.php` registriert werden. Dies erhöht die Transparenz und verhindert "Magie".
*   **Constructor Injection:** Abhängigkeiten werden ausschließlich über den Konstruktor übergeben. Das stellt sicher, dass ein Service immer in einem validen Zustand instanziiert wird.

*Beispiel-Konfiguration (Auszug aus services.php):*

```php
// Registrierung des AuthenticationService mit Interface-Injection
$container->register(AuthenticationService::class, AuthenticationService::class)
    ->addArgument(new Reference('session_service'))
    ->addArgument(new Reference(UserRepositoryInterface::class)) // Interface statt Klasse!
    ->addArgument(new Reference('security_logger'))
    ->setPublic(true);


---

### 2.3 Request Lifecycle (Der Weg einer Anfrage)

1.  **Einstieg (Entry Point):**
    Der Webserver leitet alle Anfragen an `public/index.php`.

2.  **Bootstrapping:**
    Die `index.php` instanziiert die Klasse `Kernel`. Dabei wird die Umgebung (`APP_ENV`) aus der `.env` Datei geladen.

3.  **Container Build:**
    Der Kernel lädt `config/services.php`, scannt Module (`modules/`) und baut den DI-Container auf.

4.  **Routing:**
    Der Kernel lädt `config/routes.php`. Der `UrlMatcher` vergleicht die URL mit den definierten Routen.

5.  **Firewall & Security (Kernel-Level):**
    Bevor der Controller aufgerufen wird, führt der Kernel Sicherheitsprüfungen durch:
    *   **Context:** Startet die Session (nur Web, kein CLI/API).
    *   **Access Control:** Prüft `#[IsPublic]` Attribute. Wenn nicht vorhanden und User ausgeloggt -> Zugriff verweigert.
    *   **Anti-Replay:** Validiert die Integrität der Session.

6.  **Controller Execution:**
    Der Resolver bestimmt den zuständigen Controller und führt die Methode aus.

7.  **Response & Terminierung:**
    *   Der Controller gibt ein `Response`-Objekt zurück.
    *   Der Kernel setzt Sicherheits-Header (CSP, HSTS).
    *   Der Kernel speichert die Session (`save()`).
    *   Die Antwort wird an den Browser gesendet.

---

## 3. Konfiguration

Die Konfiguration von Nexus erfolgt auf drei Ebenen, je nach Art der Einstellung:

1.  **Environment (.env):**
    Sensible Daten (Secrets, Passwörter) und Infrastruktur-Parameter (Datenbank-DSN, Redis-Host). Diese Datei darf nicht versioniert werden.

2.  **Dependency Injection (config/services.php):**
    Verdrahtung der Architektur. Hier wird definiert, welche Implementierung (z.B. `DatabaseUserRepository`) für welches Interface (`UserRepositoryInterface`) genutzt wird.

3.  **Feature Toggles (config/modules.json):**
    Anwendungssteuerung zur Laufzeit (z.B. "Modul XY aktivieren"). Diese Datei wird vom `ConfigService` verwaltet und kann über das Admin-Panel bearbeitet werden.

### 3.1 Umgebungsvariablen (.env)

Die Datei `.env` im Wurzelverzeichnis steuert das Verhalten der Infrastruktur. Sie wird **niemals** in die Versionskontrolle eingecheckt.

**Wichtige Variablen:**

*   **APP_ENV:**
    *   `development`: Aktiviert detailliertes Debugging (Tracy), deaktiviert Caches, zeigt Stacktraces bei Fehlern.
    *   `production`: Optimiert für Performance, aktiviert Caching, unterdrückt Fehlermeldungen (zeigt stattdessen benutzerfreundliche Fehlerseiten).
*   **ADMIN_USER / ADMIN_EMAIL:**
    *   Definiert den Benutzernamen und die E-Mail für den initialen Admin-Zugang (Root-User).
*   **ADMIN_PASSWORD_HASH:**
    *   Der Argon2id-Hash des Admin-Passworts. Muss in einfachen Anführungszeichen `'...'` stehen, um Parsing-Fehler durch `$`-Zeichen zu vermeiden.
*   **APP_SECRET:**
    *   Ein zufälliger String (min. 32 Zeichen), der zum Salten von Session-Fingerprints und CSRF-Tokens verwendet wird.
*   **DB_DSN:**
    *   Die Datenbankverbindung (z.B. `mysql:host=127.0.0.1;dbname=nexus`). Wird vom `DatabaseService` genutzt.
*   **SESSION_HANDLER:**
    *   `native` (File), `redis` oder `database`. Steuert, wo Sessions gespeichert werden.

### 3.2 Service-Konfiguration (config/services.php)

Hier wird der **Dependency Injection Container** konfiguriert. Wenn du einen neuen Controller oder Service erstellst, **musst** du ihn hier registrieren.

*   **Prinzip:** Wir nutzen explizite Service-Definitionen. Es gibt kein "Auto-Wiring" oder Scannen von Klassen. Jeder Service muss definiert werden.
*   **Modularität:** Um Module zu unterstützen, lädt der Container jedoch automatisch die `config/services.php`-Dateien aus dem `modules/`-Verzeichnis. Die Services *innerhalb* dieser Dateien sind wiederum explizit definiert.
*   **Parameter:** Globale Pfade (z.B. Projekt-Root) werden hier als Argumente an Services übergeben, um harte Pfadabhängigkeiten im Code zu vermeiden.

### 3.3 Routing (config/routes.php)

Definiert die Zuordnung von URLs zu Controllern.

*   **Reihenfolge:** Die Definition erfolgt von "Spezifisch" nach "Allgemein".
    1.  Admin-Routen (`/admin/...`)
    2.  Statische Seiten (`/impressum`)
    3.  Dynamische Dummy-Seiten (`/{slug}`)
    4.  Fallback (`/` bzw. 404)
*   **Konflikte:** Neue Routen sollten immer *vor* der Route für dynamische Seiten eingefügt werden, damit sie nicht als Slug interpretiert werden.
*   **Module:** Routen aus `modules/*/config/routes.php` werden automatisch geladen und VOR den dynamischen Seiten eingefügt, damit Module Vorrang haben.
*   **API:** API-Routen (`/api/v1/...`) folgen denselben Regeln und nutzen den `ApiTokenAuthenticator`.

### 3.4 Feature Toggles (config/modules.json)

Nexus verfügt über ein integriertes System für Feature-Toggles, um Funktionen modular an- oder abzuschalten.

*   **Speicherort:** `config/modules.json`.
*   **Verwaltung:** Diese Datei wird automatisch vom `ConfigService` verwaltet. Änderungen erfolgen über das Admin-Dashboard. Manuelle Änderungen sind möglich, aber nicht empfohlen.
*   **Nutzung im Code:**
    *   Im PHP: `$this->configService->isEnabled('module_user_management')`
    *   Im Twig Template: `{% if config('module_user_management') %} ... {% endif %}`

**Verfügbare Module (Stand v0.x):**
*   `module_user_management`: Steuert Login/Registrierung im Frontend.
*   `module_site_search`: Blendet das Suchfeld im Header ein/aus.
*   `module_cookie_banner`: Aktiviert den Consent-Manager.
*   `module_language_selection`: Aktiviert den Sprachwähler.

---

## 4. Frontend-Entwicklung

Nexus nutzt **Vite** für das Asset-Management.

*   **Source:** `public/css/` und `public/js/`.
*   **Build:** `npm run build` erzeugt optimierte Dateien in `public/build/`.
*   **Architektur:**
    *   **CSS:** Native CSS mit BEM-Methodik und CSS Variables (kein SASS).
    *   **JS:** ES6 Module.
*   **Workflow:** Änderungen an CSS/JS sind erst nach einem Build im Browser sichtbar (oder via Vite Dev Server, falls konfiguriert).

---

## 5. Testing & Qualitätssicherung

Qualitätssicherung ist integraler Bestandteil der Entwicklung. Die CI-Pipeline bricht ab, wenn Tests fehlschlagen oder die Coverage unter 90% fällt.

### 5.1 Test-Framework & Struktur

Wir verwenden **PHPUnit** für Tests und **PHPStan** für statische Analyse.

*   **Verzeichnis:** `tests/`.
*   **Struktur:**
    *   `tests/Unit/`: Testet einzelne Klassen isoliert (Services, Domain-Logik). Hier wird intensiv gemockt.
    *   `tests/Integration/`: Testet das Zusammenspiel (Controller, Kernel, Routing).
*   **Abdeckung:** Ziel ist > 90% Code Coverage für die Domain-Schicht.

### 5.2 Tests ausführen

Stelle sicher, dass die Abhängigkeiten installiert sind (`composer install`).

**Standard-Ausführung:**
Führt alle Tests aus und zeigt Punkte (`.`) für Erfolge an.
vendor/bin/phpunit

**Detaillierte Ausgabe (Empfohlen):**
Zeigt die Namen der getesteten Szenarien (Testdox-Format).
vendor/bin/phpunit --testdox

**Coverage Report:**
Erzeugt einen HTML-Bericht im Ordner `coverage/`, um ungetesteten Code zu finden.
vendor/bin/phpunit --coverage-html coverage

### 5.3 Isolation von Seiteneffekten

Unit-Tests dürfen keine bleibenden Spuren im System hinterlassen (keine Dateien im Projektordner, keine DB-Einträge).

*   **Dateisystem:** Wir nutzen temporäre Verzeichnisse (`sys_get_temp_dir`) oder Mocking der Repositories (`FilePageRepository` wird gemockt, statt das Dateisystem zu testen).
*   **Best Practice:** Pfade müssen injizierbar sein. Hardcodierte Pfade (`__DIR__ . '/../config'`) verhindern Testbarkeit.

### 5.4 Continuous Integration (CI)

Das Projekt verfügt über eine automatisierte CI-Pipeline via **GitHub Actions**.

*   **Konfiguration:** `.github/workflows/ci.yml`
*   **Trigger:** Bei jedem `push` oder `pull_request` auf den `main`/`master` Branch.
*   **Ablauf:**
    1.  Bereitstellen einer Ubuntu-Umgebung.
    2.  Installation von PHP (Matrix: 8.2, 8.3) und Extensions.
    3.  Erstellen einer temporären `.env` für die Pipeline.
    4.  **Static Analysis:** Prüfung mit `php-cs-fixer` (Style) und `phpstan` (Typen).
    5.  **Security Audit:** Prüfung auf verwundbare Abhängigkeiten (`composer audit`).
    6.  **Tests:** Ausführung aller Tests mit Coverage-Report.
    7.  **Versioning:** Automatisches Tagging bei erfolgreichem Master-Build.

**Regel:** Ein roter CI-Build gilt als "broken" und darf nicht deployed werden.