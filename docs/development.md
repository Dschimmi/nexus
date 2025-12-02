# Nexus Framework - Entwicklerdokumentation

Willkommen im Entwickler-Handbuch für das Projekt **Nexus** (Codename: Exelor).
Dieses Dokument dient als zentrale Referenz für die Architektur, Installation und Weiterentwicklung des Frameworks.

---

## 1. Installation und Inbetriebnahme

### 1.1 Systemvoraussetzungen
Um Nexus lokal zu entwickeln oder zu betreiben, muss die Umgebung folgende Voraussetzungen erfüllen:

*   **PHP:** Version **8.2** oder höher.
*   **Erweiterungen:** `mbstring`, `intl`, `pdo`, `json`.
*   **Composer:** Version **2.x**.
*   **Webserver:** Apache 2.4+ (mit `mod_rewrite`) oder Nginx.
*   **Datenbank:** Aktuell nicht erforderlich (Dateibasiertes System in Version 0.x).

### 1.2 Schritt-für-Schritt Installation

**1. Repository klonen**
git clone https://github.com/Dschimmi/nexus.git
cd nexus

**2. Abhängigkeiten installieren**
Wir nutzen Composer für das Backend-Dependency-Management.
composer install

**3. Umgebungskonfiguration (.env)**
Erstelle eine `.env` Datei im Wurzelverzeichnis. Da wir keine sensiblen Daten im Repository speichern, musst du diese Datei lokal anlegen.

Beispielinhalt für `.env`:

APP_ENV=development

# Admin-Zugang (Datei-basiertes Backend)
ADMIN_USER=admin
ADMIN_EMAIL=admin@localhost
# Passwort-Hash generieren mit: php -r "echo password_hash('DeinPasswort', PASSWORD_ARGON2ID);"
# WICHTIG: Den Hash in einfache Anführungszeichen setzen!
ADMIN_PASSWORD_HASH='$argon2id$v=19$m=65536,t=4,p=1$...' 


**4. Dateisystem-Berechtigungen**
Stelle sicher, dass der Webserver Schreibrechte auf folgende Verzeichnisse hat:
*   `public/pages/` (Für generierte Dummy-Seiten)
*   `config/` (Zum Speichern der `modules.json`)
*   `public/sitemap.xml` (Für SEO)

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

### 2.1 Schichten-Modell (Layers)

Der Quellcode in `src/` ist logisch in Schichten unterteilt:

**1. Kernel Layer (`src/Kernel/`)**
Der Einstiegspunkt der Anwendung. Der Kernel initialisiert den Dependency Injection Container, lädt die Konfiguration und wandelt einen eingehenden `Request` in eine `Response` um.

**2. Presentation Layer (`src/Controller/` & `templates/`)**
Die Schnittstelle nach "außen" zum Benutzer (HTTP).
*   **Controller:** Nehmen Anfragen entgegen, validieren Input und rufen Services auf. Sie enthalten *keine* komplexe Geschäftslogik.
*   **Templates:** Twig-Dateien für die HTML-Ausgabe.

**3. Application & Domain Layer (`src/Service/`)**
Das Herzstück der Anwendung. Hier liegt die Geschäftslogik.
*   Services sind zustandslos (stateless) und wiederverwendbar.
*   Beispiele: `AuthenticationService` (Prüfung von Credentials), `PageManagerService` (Verwaltung von Inhalten), `ConfigService` (Feature Toggles).

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

// Registrierung des AdminControllers mit seinen Abhängigkeiten
$container->register(AdminController::class, AdminController::class)
    ->addArgument(new Reference(Environment::class))            // Twig Template Engine
    ->addArgument(new Reference(AuthenticationService::class))  // Auth Logik
    ->addArgument(new Reference('config_service'))              // Config Service
    ->setPublic(true);


---

### 2.3 Request Lifecycle (Der Weg einer Anfrage)

1.  **Einstieg (Entry Point):**
    Der Webserver leitet alle Anfragen an `public/index.php`.

2.  **Bootstrapping:**
    Die `index.php` instanziiert die Klasse `Kernel`. Dabei wird die Umgebung (`APP_ENV`) aus der `.env` Datei geladen.

3.  **Container Build:**
    Der Kernel lädt `config/services.php` und baut den DI-Container auf.

4.  **Routing:**
    Der Kernel lädt `config/routes.php`. Der `UrlMatcher` vergleicht die URL mit den definierten Routen.
    *   *Wichtig:* Die Reihenfolge der Routen ist entscheidend! Spezifische Routen (z.B. `/admin`) müssen vor generischen Routen (z.B. `/{slug}`) definiert werden.

5.  **Controller Execution:**
    Der Resolver bestimmt den zuständigen Controller und führt die Methode aus.

6.  **Response:**
    Der Controller gibt ein `Response`-Objekt zurück (meist gerendertes HTML), das vom Kernel an den Browser gesendet wird.

---

## 3. Konfiguration

Die Konfiguration von Nexus erfolgt auf drei Ebenen, je nach Art der Einstellung:
1.  **Environment (.env):** Sensible Daten und Server-Einstellungen.
2.  **Dependency Injection (config/services.php):** Verdrahtung der Architektur.
3.  **Feature Toggles (config/modules.json):** Anwendungssteuerung zur Laufzeit.

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

### 3.2 Service-Konfiguration (config/services.php)

Hier wird der **Dependency Injection Container** konfiguriert. Wenn du einen neuen Controller oder Service erstellst, **musst** du ihn hier registrieren.

*   **Prinzip:** Wir nutzen explizite Definitionen. Es gibt kein "Auto-Discovery".
*   **Parameter:** Globale Pfade (z.B. Projekt-Root) werden hier als Argumente an Services übergeben, um harte Pfadabhängigkeiten im Code zu vermeiden.

### 3.3 Routing (config/routes.php)

Definiert die Zuordnung von URLs zu Controllern.

*   **Reihenfolge:** Die Definition erfolgt von "Spezifisch" nach "Allgemein".
    1.  Admin-Routen (`/admin/...`)
    2.  Statische Seiten (`/impressum`)
    3.  Dynamische Dummy-Seiten (`/{slug}`)
    4.  Fallback (`/` bzw. 404)
*   **Konflikte:** Neue Routen sollten immer *vor* der Route für dynamische Seiten eingefügt werden, damit sie nicht als Slug interpretiert werden.

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

## 4. Testing & Qualitätssicherung

Qualitätssicherung ist kein nachgelagerter Schritt, sondern integraler Bestandteil der Entwicklung bei Nexus. Wir setzen auf automatisierte Unit-Tests, um die Stabilität der Geschäftslogik zu gewährleisten.

### 4.1 Test-Framework & Struktur

Wir verwenden **PHPUnit** als Testing-Framework.

*   **Verzeichnis:** Alle Tests liegen im Ordner `tests/`.
*   **Struktur:**
    *   `tests/Unit/`: Testet einzelne Klassen isoliert (Services, Helper).
    *   *(Geplant: `tests/Integration/`: Testet das Zusammenspiel mehrerer Komponenten).*
*   **Namenskonvention:** Testklassen müssen auf `Test.php` enden (z.B. `AuthenticationServiceTest.php`).

### 4.2 Tests ausführen

Stelle sicher, dass die Abhängigkeiten installiert sind (`composer install`).

**Standard-Ausführung:**
Führt alle Tests aus und zeigt Punkte (`.`) für Erfolge an.
vendor/bin/phpunit

**Detaillierte Ausgabe (Empfohlen):**
Zeigt die Namen der getesteten Szenarien (Testdox-Format).
vendor/bin/phpunit --testdox

### 4.3 Mocking & Dateisystem-Tests (vfsStream)

Da viele Services von Nexus auf das Dateisystem zugreifen (z.B. `PageManagerService` schreibt HTML, `ConfigService` schreibt JSON), müssen diese Zugriffe in Unit-Tests isoliert werden. Wir wollen **keine** echten Dateien auf der Festplatte während der Tests erstellen.

**Lösung: vfsStream**
Wir nutzen die Bibliothek `mikey179/vfsStream`, um ein virtuelles Dateisystem im Arbeitsspeicher zu simulieren.

**Beispiel:**
Wenn ein Service eine Datei speichern soll, injizieren wir ihm im Test nicht den echten Pfad zum Projekt, sondern eine URL zu `vfs://root`.

*   **Vorteile:**
    1.  **Geschwindigkeit:** RAM ist schneller als SSD.
    2.  **Isolation:** Tests beeinflussen sich nicht gegenseitig.
    3.  **Sauberkeit:** Keine temporären Dateien, die manuell gelöscht werden müssen.

**Wichtig für Entwickler:**
Wenn du einen Service schreibst, der Datei-Pfade nutzt, darfst du `dirname(__DIR__)` oder `__DIR__` **nicht** hardcodieren. Der Basispfad muss immer über den Konstruktor injiziert werden, damit er im Test durch `vfsStream::url('root')` ersetzt werden kann.

### 4.4 Continuous Integration (CI)

Das Projekt verfügt über eine automatisierte CI-Pipeline via **GitHub Actions**.

*   **Konfiguration:** `.github/workflows/ci.yml`
*   **Trigger:** Bei jedem `push` oder `pull_request` auf den `main`/`master` Branch.
*   **Ablauf:**
    1.  Bereitstellen einer Ubuntu-Umgebung.
    2.  Installation von PHP (Matrix: 8.2, 8.3) und Extensions.
    3.  Erstellen einer temporären `.env` für die Pipeline.
    4.  Ausführung aller Tests.

**Regel:** Ein roter CI-Build gilt als "broken" und darf nicht deployed werden.