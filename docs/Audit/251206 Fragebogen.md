# **Anforderungen an das Nexus-Framework**

## **1. Basisframework (Core Application Scaffolding)**

### **1.1 Seiten-Rendering und Template-Management**
- Mechanismus zur Verarbeitung von Anfragen über Routing-Komponente an Controller, der Twig-Templates rendert und als HTML-Seite ausliefert.
- Flexible Templates, die von späteren Modulen dynamisch mit Inhalten befüllt werden können.
- Keine datenbankgestützte Inhaltsverwaltung im Basisframework.

### **1.2 Sichere Session-Verwaltung**
- Objektorientierter SessionService mit strukturierten "Attribute Bags" (Security Bag, Attribute Bag, Flash Bag).
- Session-ID mit mindestens 128 Bit Entropie (`random_bytes()`).
- Session-Fingerprinting (Browser-Familie, Major-Version, Betriebssystem-Plattform, anonymisierte IP-Adresse, optional Geolokation).
- Regeneration der Session-ID bei Login, Logout, Passwortänderungen, Rollenwechsel oder sensiblen Transaktionen.
- Invalidation-Methode zur Zerstörung der Session serverseitig und clientseitig.
- Anti-Replay-Mechanismus (security_version im Security-Bag).
- Storage-Abstraktion über `SessionHandlerInterface` (Dateisystem, Redis, DB).
- Cookie-Konfiguration: `HttpOnly`, `Secure`, `SameSite=Strict`, Name Obfuscation, kein `PHPSESSID`.
- Lifetime-Management: Strict Idle Timeout, Absolute Max Lifetime, Cookie Lifetime synchron zum Idle-Timeout.
- CSRF-Schutz: Automatische Generierung und Verwaltung von CSRF-Tokens im Security Bag.
- Ereignisprotokollierung: Strukturierte Logs für Session-Migration, Invalidation, Fingerprint-Mismatch, CSRF-Token-Fehler.
- Multi-Tab- und Race-Condition-Sicherheit: Timestamp-basierte Versionierung, atomare Schreibvorgänge.
- Kontextsteuerung: Keine automatische Session in CLI, explizites Opt-In für Web-APIs/Controller.

### **1.3 Internationalisierung (i18n)**
- Zentraler Translator-Service mit `TranslatorInterface`.
- Standard-Provider für Sprachstrings aus PHP-Dateien (`/translations/de.php`, `/translations/en.php`).
- Erweiterbar für Datenbank-Provider oder andere Quellen.

### **1.4 DSGVO- und Compliance-Mechanismen**
- Zentraler Service zur Verwaltung von Benutzerzustimmungen (Cookie-Consent).
- Rudimentäres Cookie-Banner (HTML/JS) mit Inhalten über i18n-Komponente.
- Speicherung und Abfrage des Zustimmungsstatus über Session-Service.

### **1.5 Performance- und Sicherheitsgrundlagen**
- Schutz vor XSS (Twig Auto-Escaping), CSRF (automatische Token-Generierung), SQL-Injection (PDO mit Prepared Statements).
- Einheitliches Logging mit Tracy.
- Konfiguration für Caching-Mechanismen (Opcode-Caching).

### **1.6 SEO-Grundlagen**
- Meta-Tag-Management: Individueller `<title>`-Tag und `<meta name="description">` pro Seite.
- Sitemap-Generierung: Mechanismus zur Erstellung einer `sitemap.xml` gemäß Standard.
- `robots.txt`-Management: Standarddatei mit Good Practices, anpassbar.
- Canonical URLs: Automatische Generierung von `rel="canonical"` für jede Seite.

---

## **2. Erweiterungsmodule**

### **2.1 Userverwaltung und Rechteverwaltung**
- Anwendungs-Services für Benutzerregistrierung, Login, Logout, Passwort-Reset.
- Authentifizierung über Benutzername + Passwort **oder** E-Mail-Adresse + Passwort.
- Kontextbezogenes, rollenbasiertes Zugriffskontrollsystem (RBAC).
- Integration in Session-Service für sichere Verwaltung des Login-Status.
- Regeneration der Session-ID nach Login.

### **2.2 Formular- und Datenmanagement**
- Form-Builder-Service zur programmgesteuerten Definition von Formularen (Felder, Typen, Labels, Validierungsregeln).
- Validierungs-Service für serverseitige Überprüfung von Formulareingaben.
- Integration in Twig für Frontend-Rendering und Anzeige von Validierungsfehlern.
- PDF-Generierungs-Service zur Erstellung von PDFs aus validierten Formulardaten (z. B. TCPDF, Dompdf).
- Automatische Integration des CSRF-Schutzes aus dem Basisframework.

### **2.3 Marketing-Integrationen**
- Script-Management-Service zur Registrierung von externen Marketing- und Analyse-Skripten (z. B. Google Analytics, Meta Pixel).
- Verknüpfung jedes Skripts mit einer Zustimmungskategorie (z. B. marketing, statistics).
- Rendering der Skripte nur bei expliziter Benutzerzustimmung.
- Twig-Funktion zum Einbinden der Skripte an definierten Stellen im HTML-Dokument.

### **2.4 Optionale Service Provider**
- **Erweiterte Sicherheitsfunktionen:**
  - TwoFactorServiceInterface für TOTP.
  - AuditTrailLoggerInterface für revisionssichere Protokollierung.
  - ContentSecurityPolicyBuilderInterface für dynamische CSP-Header.

---

## **3. Spezialmodule**

### **3.1 CMR-Frachtbriefgenerator**
- Geschütztes Web-Formular zur Erfassung von Frachtbriefdaten.
- Zugriffssteuerung über Userverwaltungsmodul.
- Erstellung und Validierung des Formulars über Form-Builder-Service.
- Generierung eines standardisierten CMR-Frachtbrief-PDFs zum Download.

### **3.2 Forum- und Communityfunktionen**
- Nutzung des Userverwaltungsmoduls für Benutzerprofile, Login und Community-spezifische Rollen.
- Definition von Domain-Entitäten: Forum, Thread, Post.
- Controller und Views für Anzeige von Foren, Threads und Beiträgen.

---

## **4. Nicht-funktionale Anforderungen**

### **4.1 Performance**
- TTFB für dynamische Seiten ≤ 500 ms.
- Google Core Web Vitals im "grünen Bereich" (LCP < 2,5 Sekunden).
- ≤ 20 SQL-Abfragen für das Rendern einer typischen Detailseite (ungecacht).
- Minifizierung und Bündelung von CSS/JS-Dateien.
- Unterstützung von Opcode- und Application-Caching.

### **4.2 Sicherheit**
- Mehrschichtige Sicherheit (Defense in Depth) gegen OWASP Top 10.
- Passwort-Speicherung ausschließlich mit `PASSWORD_ARGON2ID`.
- Zugriffskontrolle nach "Deny by Default".
- HTTPS-Erzwingung im Produktionsmodus.
- Regelmäßige Überprüfung externer Abhängigkeiten auf Sicherheitslücken.
- Secret Management über Environment-Variablen.

### **4.3 Skalierbarkeit**
- Stateless Application für horizontale Skalierbarkeit.
- Zentralisierte Zustandsverwaltung für Sessions (Redis, DB) und Dateiuploads (NFS, S3).
- Lose Kopplung für ressourcenintensive Aufgaben (Queues).

### **4.4 Wartbarkeit**
- Einhaltung der Coding Guidelines (PSR-12, SOLID, DDD).
- Modulare und entkoppelte Architektur.
- Automatisierte Tests (Unit/Integration) mit Code-Coverage > 90 % für Domain-Logik.
- Dokumentation als Markdown im Repository (`/docs`).
- Standardisierte Prozesse für Versionierung und Deployment.

### **4.5 Usability**
- **Für Entwickler:**
  - Klare Schnittstellen (APIs) mit DocBlocks.
  - Debugging-Unterstützung über Tracy.
  - Umfassende technische Dokumentation.
- **Für Endnutzer:**
  - Responsive Design für alle Bildschirmgrößen.
  - Barrierefreiheit nach WCAG 2.1 AA.
  - Konsistente Bedienung und Anordnung von UI-Elementen.
  - Umfassendes System-Feedback (Ladeindikatoren, Erfolgs-/Fehlermeldungen).

---

## **5. Schnittstellen und Integrationen**

### **5.1 Datenbank-Schnittstellen**
- Repository Pattern für Datenzugriff.
- PDO mit Prepared Statements.
- Domänenspezifische API für Datenzugriff (z. B. `findUserById(int $id)`).

### **5.2 Externe Datenschnittstellen (Import/Export)**
- Adapter- und Transformationsschicht für externe Datenformate (CSV, TSV).
- Konfigurierbarer Importprozess mit Mapping-Konfigurationen.
- Bidirektionale Transformation für Export.

### **5.3 Authentifizierungs- und Autorisierungsdienste**
- Flexible Authentication Provider-Architektur.
- Unterstützung für LDAP/Active Directory, OAuth 2.0, SAML.
- Just-in-Time Provisioning für externe Benutzer.

### **5.4 REST-API**
- Standardisierte REST-API mit JSON-Datenaustausch.
- Authentifizierung über API-Tokens (Bearer Tokens).
- Versionierung der API (z. B. `/api/v1/`).
- Webhooks für Echtzeit-Benachrichtigungen.

---

## **6. Design-, Barrierefreiheits- und Usability-Vorgaben**

### **6.1 Grundlegendes Designsystem und Theming**
- Zentrales Designsystem mit nativem CSS (keine Präprozessoren).
- BEM-Methodik für Klassennamen.
- Theming-Architektur über CSS Custom Properties.
- Modul-spezifisches CSS mit striktem Prefixing.

### **6.2 Barrierefreiheit (Accessibility)**
- WCAG 2.1 AA-Konformität.
- Tastaturbedienbarkeit für alle interaktiven Elemente.
- Semantisches HTML und ARIA-Landmarks.
- Farbkontraste: 4.5:1 für Text, 3:1 für großen Text.

### **6.3 UI-Komponenten**
- **Header:** Logo, Light-/Darkmode-Umschalter, Sprachauswahl, Login/Registrierung.
- **Subheader:** Navigation mit Browsertabs, Dropdown-Unterstützung.
- **Content-Bereich:** Lesbare Breite, neutrale Hintergrundfarbe, visuelle Abgrenzung.
- **Footer:** Copyright, Impressum, Datenschutz, Sitemap, Social-Media-Icons.
- **Cookie-Banner:** Zentral, modal, blockierend, mit Zustimmungsoptionen.

---

## **7. Rahmenbedingungen**

### **7.1 Gesetzliche und Regulatorische Anforderungen**
- DSGVO/TDDDG: Privacy by Design, Datenminimierung, Cookie-Consent.
- BFSG: Barrierefreiheit nach WCAG 2.1 AA.
- Serverstandort: EU (bevorzugt Deutschland).

### **7.2 Standards und Normen**
- IT-Sicherheit: BSI IT-Grundschutz, ISO 27001.
- Lizenzrecht: Nur kompatible Lizenzen (MIT, Apache 2.0, BSD).

### **7.3 Hosting und Betrieb**
- Systemvoraussetzungen: PHP 8.2+, MySQL/PostgreSQL, Nginx/Apache.
- Backup: Dateisystem + SQL-Dump.
- Wartungsmodus: Konfigurierbare "Wartungsarbeiten"-Seite.

### **7.4 Umgebungs-spezifische Konfiguration**
- Steuerung über Environment-Variablen (z. B. `APP_ENV`).
- Fail-Safe-Prinzip: Default zu Production-Modus.
- `.env`-Datei für lokale Entwicklung, nie im Repository.

---

## **8. Qualitätssicherung und Testkonzept**

### **8.1 Maßnahmen zur Sicherstellung der Softwarequalität**
- Coding Guidelines: PSR-12, SOLID, DDD, statische Analyse mit PHPStan/Psalm.
- Teststrategie: Unit- und Integrationstests mit PHPUnit, Code-Coverage > 90 %.
- Dokumentation: DocBlocks, ADRs, technische Dokumentation im Repository.

### **8.2 Teststrategie und Automatisierung**
- Automatisierte Test-Suite für alle Kernkomponenten.
- CI/CD-Pipeline mit Linting, Tests, Build und Deployment.

---

## **9. Abnahmekriterien und Inbetriebnahme**

### **9.1 Formale Abnahmekriterien**
- Vollständige Implementierung aller funktionalen Anforderungen.
- Erfüllung aller nicht-funktionalen Anforderungen (Performance, Sicherheit, Skalierbarkeit).
- Erfolgreiche Test-Suite, keine bekannten Bugs.
- Vollständige Dokumentation.

### **9.2 Prozess der Inbetriebnahme (Go-Live)**
- Bereitstellung der Produktivumgebung.
- Finales Deployment über CI/CD-Pipeline.
- Konfiguration der Environment-Variablen.
- Smoke-Test und formale Übergabe.

---

## **10. Projektorganisation und Meilensteine**

### **10.1 Rollen und Verantwortlichkeiten**
- **Auftraggeber:** Fachliche Anforderungen, Priorisierung, Abnahme.
- **Auftragnehmer:** Technische Konzeption, Implementierung, Qualitätssicherung.

### **10.2 Entwicklungsmeilensteine**
- **Version 0.1.0:** Grundgerüst und Rendering-Pipeline.
- **Version 0.2.0:** Sichere Session-Verwaltung und Konfiguration.
- **Version 0.3.0:** DSGVO- und Compliance-Mechanismen.
- **Version 0.4.0:** Internationalisierung (i18n).
- **Version 0.5.0:** Umgebungs-Handling, Logging, Sicherheits-Härtung.
- **Version 0.6.0:** Standard-Layout und UI-Komponenten.
- **Version 0.7.0:** Volle Funktionalität der Standardseiten.
- **Version 0.8.0:** Test-Suite, CI-Pipeline, Entwicklerdokumentation.
- **Version 0.9.0:** Stabilisierung und Abnahme.
- **Version 1.0.0:** Produktivversion (fehlerfrei, abgenommen).

---

## **11. Dokumentations-Artefakte**
- **Entwicklerdokumentation:** Installation, Architektur, Konfiguration, Kern-Services, Testing.
- **Quellcode-Dokumentation:** DocBlocks für öffentliche Klassen/Methoden.
- **ADRs:** Wichtige Architekturentscheidungen im `/docs/adr`-Verzeichnis.

---
