## 9. Qualitätssicherung und Testkonzept
`(Planung der Maßnahmen zur Sicherstellung der Softwarequalität, inklusive Teststrategie, Testarten und Automatisierung.)`

Grundsatz:
Dieses Kapitel definiert die Gesamtheit der Prozesse, Standards und Werkzeuge, die zur Sicherstellung einer durchgängig hohen Softwarequalität im gesamten Lebenszyklus des "Nexus"-Projekts eingesetzt werden. Qualität wird hierbei nicht als nachträglicher Prüfschritt verstanden, sondern als integraler Bestandteil aller Phasen von der Konzeption über die Entwicklung bis hin zum Betrieb. Die hier definierten Maßnahmen sind für alle am Projekt beteiligten Entwickler verbindlich.

### 9.1. Maßnahmen zur Sicherstellung der Softwarequalität

#### 9.1.1. Coding Guidelines

##### 9.1.1.1. Allgemeine Grundsätze
- Lesbarer, wartbarer, sicherer Code.
- Fail-Fast-Strategie: Unsichere Zustände sofort abbrechen.
- Separation of Concerns: Domain, Application, Infrastructure, Presentation strikt trennen. Die Architektur orientiert sich an Domain-Driven Design (DDD) und/oder Hexagonaler Architektur, um eine klare Trennung der Verantwortlichkeiten und eine gemeinsame Terminologie im Team zu gewährleisten.
- Alle Eingaben validieren, bevor Business-Logik ausgeführt wird.
- Technische Absicherung: Der Einsatz von statischen Analyse-Tools (z. B. PHPStan auf hohem Level, Psalm) ist verpflichtend, um Typsicherheit und die Einhaltung der Schichtentrennung bereits vor der Ausführung zu überprüfen.

##### 9.1.1.2. Dateistruktur & Namenskonventionen
/app
    /Domain          # Enthält Entities, Value Objects, Repository-Interfaces und Domain-Services. Frei von Infrastruktur-Code.
    /Application     # Anwendungslogik, Services, Use Cases, DTOs
    /Infrastructure  # Enthält konkrete Implementierungen wie Repositories (z. B. PostgresUserRepository), externe API-Clients, etc.
    /Presentation    # Controller, Views, Templates, API-Endpunkte
/config
/log
/temp
/public
/vendor
/tests
- Klassen: PascalCase (UserRepository)
- Methoden: camelCase (resetPassword())
- Variablen: camelCase ($accessToken)
- Konstanten: UPPER_CASE (TOKEN_TTL)
- Dateinamen: Dateinamen müssen dem Namen der enthaltenen Klasse entsprechen (z. B. UserRepository.php für die Klasse UserRepository).
Bootstrap (z. B. bootstrap.php):
Tracy\Debugger::enable(Tracy\Debugger::DETECT, __DIR__ . '/log');

##### 9.1.1.3. PHP-Standards (PSR-12)
- Deklarationen: declare(strict_types=1);
- Einrückung: 4 Leerzeichen
- Zeilenlänge: ≤ 120 Zeichen
- Ausgabe-Funktionen: Keine Verwendung von echo, var_dump() oder ähnlichen Funktionen im Produktionscode.
- Debugging: 
  - bdump() wird bevorzugt, da es die Ausgabe in die Tracy Debug Bar umleitet und den Programmfluss nicht unterbricht.
  - dump() stoppt die Ausführung und ist nur für gezieltes Debugging gedacht.
- Automatisierung: Die Einhaltung von PSR-12 wird durch den Einsatz von Tools wie PHP-CS-Fixer oder ECS (Easy Coding Standard) sichergestellt. Diese Tools sind in die Entwicklungsumgebung und die CI-Pipeline integriert, um Code-Reviews effizienter zu gestalten.

##### 9.1.1.4. OOP-Prinzipien
- SOLID-Prinzipien konsequent anwenden.
- Dependency Injection: 
  - Constructor Injection ist der Standard, um Abhängigkeiten explizit zu machen und sicherzustellen, dass Objekte immer in einem validen Zustand erstellt werden.
  - Als DI-Container wird Symfony DI eingesetzt, um Autowiring und die zentrale Konfiguration von Services zu handhaben.
- Repository Pattern für Datenzugriffe.
- Service Layer für Anwendungslogik.
- Entities und Value Objects klar unterscheiden.
- Interfaces für eine testbare Architektur.

##### 9.1.1.5. Fehlerbehandlung & Exceptions
- Exceptions statt Rückgabewerte für Fehlerzustände.
- Eigene Exception-Hierarchie im Namespace App\Exception.
- Ungefangene Exceptions werden an Tracy weitergeleitet.
- Standard-Log-Levels (Tracy\ILogger):
  - ILogger::INFO: Informative Ereignisse (z. B. erfolgreiche Operationen, Statusänderungen).
  - ILogger::WARNING: Potenziell problematische, aber nicht kritische Ereignisse (z. B. unerwartete Eingabewerte).
  - ILogger::ERROR: Fehler, die die Funktionalität beeinträchtigen (z. B. fehlgeschlagene Datenbankoperationen).
  - Benutzerdefinierte Kategorien (z. B. 'security') bleiben erhalten, um spezifische Logikbereiche zu kennzeichnen.
- Kontrollierte Fehler mit Tracy-Logger protokollieren: `$this->logger->log('Unauthorized access attempt', 'security');`

##### 9.1.1.6. Sicherheitsstandards (OWASP + Tracy)
Das sichere Session-Handling ist ausführlich in Kapitel 4.1.2 beschrieben, an dieser Stelle sind nur die absoluten Minimalstandards für das Session-Handling aufgeführt.

###### 9.1.1.6.1 Session Management
- Session früh starten
- Session-ID bei Login und alle 5–10 Minuten regenerieren
- Fingerprinting (User-Agent + IP-Präfix)
  - Der Mechanismus muss tolerant gegenüber kleineren Änderungen sein (z. B. im Mobilfunknetz nur das /24-Subnetz prüfen), um die Benutzerfreundlichkeit nicht zu beeinträchtigen.
- Bei Mismatch: `\Tracy\Debugger::log('Session fingerprint mismatch', 'security');`
- Keine Session-Daten per URL übertragen
- Strict-Mode & Cookie-Flags setzen (httponly, secure, samesite=strict)

###### 9.1.1.6.2 CSRF
- CSRF-Schutz für alle State-changing Aktionen
- Bei ungültigen Tokens: `$this->logger->log('CSRF token mismatch', 'security');`

###### 9.1.1.6.3 Passwort & Authentifizierung
- Passwort-Hashes mit PASSWORD_ARGON2ID.
- Passwort-Policy (zentral durchgesetzt): 
  - Mindestlänge: 12 Zeichen.
  - Komplexität: Groß-/Kleinbuchstaben, Ziffern, Sonderzeichen.
  - Muss über das Admin-Panel konfigurierbar sein
- Hashes nie loggen.
- Audit-Logging für sicherheitsrelevante Vorgänge.
- Rate-Limits für Login-Versuche.

##### 9.1.1.7. Controller-Regeln
- Controller bleiben schlank
- Nur Request-Handling, Übergabe an Services
- Keine SQL-Queries im Controller
- Nachvollziehbares Logging sicherheitsrelevanter Aktionen: `$this->logger->log("User {$userId} updated profile", 'info');`
- Für komplexere Anwendungsfälle kann die Verwendung von Single Action Controllern (mit __invoke-Methode) empfohlen werden, um die Granularität und Übersichtlichkeit zu erhöhen.

##### 9.1.1.8. Datenbank & Repositories
- PDO mit Prepared Statements
- Transaktionen bei komplexen Operationen
- SQL-Fehler: (als Exception weiterwerfen)
- Keine PDOException nach außen leaken. Repositories müssen diese fangen und in domänenspezifische Exceptions umwandeln (z. B. CouldNotFindUserException, CouldNotSaveUserException).
- Dies verhindert, dass Infrastrukturdetails in die Anwendungslogik durchsickern.
- Fehler in Repositories loggen: `$this->logger->log($exception, 'error');`
- Optional: Tracy Database Panel integrieren

##### 9.1.1.9. Logging & Monitoring (TRACY)

###### 9.1.1.9.1 Grundsatz
- Tracy ist das zentrale Logging-System
- Minimale PHP-Version: PHP 8.2 (erforderlich für Features wie Constructor Property Promotion).
- ILogger wird in Services und Repositories injiziert: 
  - `use Tracy\ILogger;`
  - `public function __construct(private ILogger $logger) {}`

###### 9.1.1.9.2 Zu loggende Ereignisse
- Login/Logout
- Passwort-Änderungen
- Rechte-Änderungen
- Account-Sperrungen
- Token-Logins
- Session-Invalidierungen
- CSRF/Replay/Suspicious Activity
- Zugriff auf geschützte Bereiche

###### 9.1.1.9.3 Nicht zu loggen
- Passwörter
- Session-IDs
- Tokens
- Private Keys
- Sensitive personenbezogene Daten

##### 9.1.1.10. Tests
- Test-Framework: PHPUnit wird als Standard-Test-Framework verwendet.
- Unit-Tests ohne Tracy
- Integrationstests mit minimaler Tracy-Konfiguration
- Kein Debug-Output im Testcode
- Mocks für Infrastruktur-Komponenten
- Code-Coverage-Ziel: > 90 % für die kritische Domain-Logik, um eine messbare Qualitätsanforderung sicherzustellen.

##### 9.1.1.11. Dokumentation
- DocBlocks Standardisierung nach phpDocumentor-Standard für öffentliche Methoden.
- Technische Architektur im Ordner /docs
- Architecture Decision Records (ADRs): Wichtige Architekturentscheidungen werden als ADRs im /docs-Ordner dokumentiert, um das "Warum" hinter Entscheidungen nachvollziehbar zu machen.
- Tracy Annotated-Debug-Panels können als Entwicklerdoku dienen

##### 9.1.1.12. Git & Deployment
- Git-Workflow: Verwendung von Trunk-Based Development mit Feature-Branches und Pull Requests.
- Pull Requests: Jeder Pull Request muss die automatisierte CI-Pipeline (Linting, Tests) erfolgreich durchlaufen, bevor er gemerged werden darf.
- Nicht versionierte Verzeichnisse: /log/ und /temp/ werden nicht versioniert.
- Environment-spezifische Konfiguration: 
  - Environment-Variablen werden über .env-Dateien (mit vlucas/phpdotenv) verwaltet.
  - Sensitive Keys/Secrets werden nie im Repository gespeichert.
- Tracy-Konfiguration: 
  - DEV: Panels aktiv, Bluescreen aktiv.
  - PROD: Keine Panels, Fehlerreports per E-Mail, sicherheitsorientiertes Logging.

##### 9.1.1.13. Performance
- Tracy im Produktionsmodus minimal halten.
- Keine Debug-Dumps im Live-Code.
- Caching: 
  - Opcode Caching: OPcache muss auf dem Server aktiviert sein.
  - Application Caching: Das Framework stellt eine Abstraktion für das Caching von Daten und Konfiguration bereit, die mit Treibern wie Redis oder APCu arbeiten kann.
- Prepared Statements wiederverwenden.
- Session-Lifetime optimieren.

##### 9.1.1.14. Verbotene Praktiken
- Debugging-Funktionen wie var_dump(), print_r(), echo oder Tracy-Dumps im Produktionscode sind verboten.
- Statische Aufrufe im Domain-Layer sind nicht erlaubt.
- SQL-Abfragen dürfen nicht in Controllern platziert werden.
- Sensible Daten (Passwörter, Tokens, personenbezogene Informationen) dürfen nicht geloggt werden.
- Manuelle Änderungen an Sessions sind nur über den Session-Service zulässig.
- Businesslogik darf nicht redundant in Controllern implementiert werden.
- Inline-HTML in PHP-Logik ist verboten – Präsentation und Anwendung müssen strikt getrennt bleiben.

#### 9.1.2. Versionierungskonventionen
Das Projekt verwendet ein erweitertes Semantic Versioning (SemVer) mit der folgenden Bedeutung:
- Version 1.0.0: Erste bugfreie Produktivversion.
- Versionen 0.x: Meilensteine (z. B. 0.1, 0.2).
- Versionen 0.0.x: Fertigstellung eines Funktionskomplexes (z. B. 0.0.1, 0.0.2).
- Versionen 0.0.0.x: Bugfixes innerhalb eines Funktionskomplexes (z. B. 0.0.0.1, 0.0.0.12).
- Beispiel: Meilenstein 0.1.0 (User-Management) könnte aus den Funktionskomplexen 0.0.1 (Registrierung), 0.0.2 (Login) und 0.0.3 (Profilverwaltung) bestehen.

Regeln für Versionssprünge:
- Ein Wechsel von 0.0.0.x auf 0.0.1 (oder höher) ist nur zulässig, wenn keine bekannten Bugs mehr vorliegen. Beispiel: 0.0.0.12 → 0.0.1 (keine Bugs). 
- Der Wechsel auf 1.0.0 erfolgt erst nach abschließender Qualitätsprüfung und Freigabe für den Produktivbetrieb.

#### 9.1.3. Bugtracker & Bugklassifizierung
- Mantis wird als zentrales System für die Fehlerverfolgung genutzt.
- Dokumentationspflicht: Jeder Bug muss mit einer klaren Beschreibung, Schritten zur Reproduktion und der betroffenen Version erfasst werden.
- Workflow-Integration: Bugs werden priorisiert und dem zuständigen Entwicklerteam zugewiesen.
- Kritikalität: 
  - Kritisch: Systemabsturz oder Datenverlust.
  - Hoch: Schwerwiegende Funktionsstörung.
  - Mittel: Beeinträchtigung der Benutzerfreundlichkeit.
  - Niedrig: Kosmetische oder minimale Probleme.
- Priorisierung: Bugs werden nach Kritikalität und Auswirkung auf den Nutzer priorisiert. 
  - Bugs in Versionen 0.0.0.x müssen vor dem Versionssprung auf 0.0.x behoben sein.
- Feature-Requests: Werden nach vollständiger Konzeptionierung und Freigabe  ebenfalls in Mantis als Typ-Feature Request erfasst.

### 9.2. Teststrategie und Automatisierung

#### 9.2.1. Teststrategie, Testarten
Grundsatz:
Jede Komponente des Nexus-Frameworks und seiner Module muss durch automatisierte Tests abgedeckt sein. Die Tests sind fester Bestandteil der Codebasis und werden in der CI-Pipeline (siehe PH: 9.1.1.12) bei jedem Commit ausgeführt.
Testarten und Werkzeuge:
- Unit-Tests: Überprüfen einzelne, isolierte Klassen (z.B. einen Service aus der Application-Schicht). Das Standard-Framework hierfür ist PHPUnit (siehe PH: 9.1.1.10).
- Integrationstests: Überprüfen das Zusammenspiel mehrerer Komponenten (z.B. ob ein Controller korrekt einen Service aufruft und dieser korrekt mit dem Repository interagiert).
- Code-Coverage: Das Ziel ist eine Testabdeckung von > 90 % für die kritische Domain-Logik, um eine messbare Qualitätsanforderung sicherzustellen (siehe PH: 9.1.1.10).

Begründung:
Die Verankerung der Teststrategie als zentraler Bestandteil des Qualitätssicherungsprozesses (statt als optionales Modul) stellt sicher, dass alle Entwicklungen für das Nexus-Ökosystem einem einheitlich hohen Qualitätsstandard folgen. Dies ist die technische Umsetzung des Ziels der "Wartbarkeit" und "Fehlerreduktion" (LH: S. 1).

#### 9.2.2. Build- und Deployment-Automatisierung (CI/CD)
Grundsatz:
Der Prozess von der Code-Änderung bis zur Bereitstellung in einer Produktivumgebung muss vollständig automatisiert sein, um manuelle Fehler zu eliminieren und eine gleichbleibend hohe Qualität sicherzustellen. Dies wird durch eine zentrale Continuous Integration / Continuous Deployment (CI/CD) Pipeline realisiert (siehe auch PH: 9.1.1.12).

Automatisierte Schritte:
Jeder Commit in das Haupt-Repository löst die folgende, verbindliche Kette von automatisierten Aktionen aus:
- Code-Analyse: Statische Code-Analyse mit Werkzeugen wie PHPStan und PHP-CS-Fixer zur Sicherstellung der Coding Guidelines (PH: 9.1.1).
- Test-Ausführung: Ausführung aller im Projekt definierten Unit- und Integrationstests (siehe PH: 9.2.1). Die Pipeline wird bei einem Fehlschlag sofort abgebrochen.
- Build-Erstellung: Dies ist die technische Umsetzung der Anforderung aus PH: 5.1.
  - Installation der PHP-Abhängigkeiten mit composer install --no-dev --optimize-autoloader.
  - Zentrale Asset-Kompilierung: Das Framework verfügt über genau eine zentrale Build-Konfiguration (z.B. vite.config.js im Root), die für das Bündeln und Minifizieren aller Frontend-Ressourcen verantwortlich ist.
- Integrationspflicht: Erweiterungs- und Spezialmodule dürfen keine eigenen Build-Pipelines oder Bundler-Konfigurationen mitbringen. Sie müssen ihre Rohdaten (CSS/JS) in einer definierten Struktur bereitstellen, sodass sie vom zentralen Build-Prozess des Hauptprojekts automatisch erfasst, verarbeitet und in das zentrale Manifest (manifest.json) aufgenommen werden.
- Ziel: Ein einziger npm run build Befehl im Root-Verzeichnis muss ausreichen, um das gesamte System inklusive aller aktiven Module produktionsfertig zu bauen.

Deployment: Das erstellte, saubere Build-Artefakt wird automatisiert auf der Zielumgebung (Staging oder Produktion) eingespielt. Es werden keine Build-Schritte auf dem Produktivserver selbst ausgeführt.

Begründung:
Die Definition einer verbindlichen CI/CD-Pipeline stellt die praktische Umsetzung der definierten Qualitätsstandards sicher. Die Automatisierung der Asset-Kompilierung ist ein wesentlicher Bestandteil, um die in Kapitel 5.1 geforderten Performance-Ziele zu erreichen.

---