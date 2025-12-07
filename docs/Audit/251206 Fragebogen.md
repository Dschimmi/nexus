# **Anforderungen an das Nexus-Framework**

## **1. Basisframework (Core Application Scaffolding)**

### **1.1 Seiten-Rendering und Template-Management**
1. Mechanismus zur Verarbeitung von Anfragen über Routing-Komponente an Controller, der Twig-Templates rendert und als HTML-Seite ausliefert.
> Die Anforderung ist erfüllt.

2. Flexible Templates, die von späteren Modulen dynamisch mit Inhalten befüllt werden können.
> Die Anforderung ist erfüllt.

3. Keine datenbankgestützte Inhaltsverwaltung im Basisframework.
> Die Anforderung ist erfüllt.

### **1.2 Sichere Session-Verwaltung**
4. Objektorientierter SessionService mit strukturierten "Attribute Bags" (Security Bag, Attribute Bag, Flash Bag).
> Die Anforderung ist erfüllt.

5. Session-ID mit mindestens 128 Bit Entropie (`random_bytes()`).
> Mantis: 0000016

6. Session-Fingerprinting (Browser-Familie, Major-Version, Betriebssystem-Plattform, anonymisierte IP-Adresse, optional Geolokation).
> Mantis: 0000021

7. Regeneration der Session-ID bei Login, Logout, Passwortänderungen, Rollenwechsel oder sensiblen Transaktionen.
> Mantis: 0000018

8. Invalidation-Methode zur Zerstörung der Session serverseitig und clientseitig.
> Die Anforderung ist erfüllt.

9. Anti-Replay-Mechanismus (security_version im Security-Bag).
> Mantis: 0000019

10. Storage-Abstraktion über `SessionHandlerInterface` (Dateisystem, Redis, DB).
> Mantis: 0000020

11. Cookie-Konfiguration: `HttpOnly`, `Secure`, `SameSite=Strict`, Name Obfuscation, kein `PHPSESSID`.
> Mantis: 0000015

12. Lifetime-Management: Strict Idle Timeout, Absolute Max Lifetime, Cookie Lifetime synchron zum Idle-Timeout.
> Mantis: 0000022

13. CSRF-Schutz: Automatische Generierung und Verwaltung von CSRF-Tokens im Security Bag.
> Mantis: 0000023

14. Ereignisprotokollierung: Strukturierte Logs für Session-Migration, Invalidation, Fingerprint-Mismatch, CSRF-Token-Fehler.
> Mantis: 0000024

15. Multi-Tab- und Race-Condition-Sicherheit: Timestamp-basierte Versionierung, atomare Schreibvorgänge.
> Mantis: 0000025

16. Kontextsteuerung: Keine automatische Session in CLI, explizites Opt-In für Web-APIs/Controller.
> Mantis: 0000026

### **1.3 Internationalisierung (i18n)**
17. Zentraler Translator-Service mit `TranslatorInterface`.
> Mantis: 0000027

18. Standard-Provider für Sprachstrings aus PHP-Dateien (`/translations/de.php`, `/translations/en.php`).
> Mantis: 0000028

19. Standard-Provider für Sprachstrings erweiterbar für Datenbank-Provider oder andere Quellen.
> Mantis: 0000029

### **1.4 DSGVO- und Compliance-Mechanismen**
20. Zentraler Service zur Verwaltung von Benutzerzustimmungen (Cookie-Consent).
> Die Anforderung ist erfüllt.

21. Rudimentäres Cookie-Banner (HTML/JS) mit Inhalten über i18n-Komponente.
> Die Anforderung ist erfüllt.

22. Speicherung und Abfrage des Zustimmungsstatus über Session-Service.
> Die Anforderung ist erfüllt.

### **1.5 Performance- und Sicherheitsgrundlagen**
23. Schutz vor XSS (Twig Auto-Escaping), CSRF (automatische Token-Generierung), SQL-Injection (PDO mit Prepared Statements).
> Mantis: 0000030

24. Einheitliches Logging mit Tracy.
> Die Anforderung ist erfüllt.

25. Konfiguration für Caching-Mechanismen (Opcode-Caching).
> Mantis: 0000032

### **1.6 SEO-Grundlagen**
26. Meta-Tag-Management: Individueller `<title>`-Tag und `<meta name="description">` pro Seite.
> Die Anforderung ist erfüllt.

27. Sitemap-Generierung: Mechanismus zur Erstellung einer `sitemap.xml` gemäß Standard.
> Die Anforderung ist erfüllt.

28. `robots.txt`-Management: Standarddatei mit Good Practices, anpassbar.
> Die Anforderung ist erfüllt.

29. Canonical URLs: Automatische Generierung von `rel="canonical"` für jede Seite.
> Die Anforderung ist erfüllt.

---
// Ab hier wird wegen fehlender Implementierung/Testbarkeit übersprungen
## **2. Erweiterungsmodule**

### **2.1 Userverwaltung und Rechteverwaltung**
30. Anwendungs-Services für Benutzerregistrierung, Login, Logout, Passwort-Reset.
31. Authentifizierung über Benutzername + Passwort **oder** E-Mail-Adresse + Passwort.
32. Kontextbezogenes, rollenbasiertes Zugriffskontrollsystem (RBAC).
33. Integration in Session-Service für sichere Verwaltung des Login-Status.
34. Regeneration der Session-ID nach Login.

### **2.2 Formular- und Datenmanagement**
35. Form-Builder-Service zur programmgesteuerten Definition von Formularen (Felder, Typen, Labels, Validierungsregeln).
36. Validierungs-Service für serverseitige Überprüfung von Formulareingaben.
37. Integration in Twig für Frontend-Rendering und Anzeige von Validierungsfehlern.
38. PDF-Generierungs-Service zur Erstellung von PDFs aus validierten Formulardaten (z. B. TCPDF, Dompdf).
39. Automatische Integration des CSRF-Schutzes aus dem Basisframework.

### **2.3 Marketing-Integrationen**
40. Script-Management-Service zur Registrierung von externen Marketing- und Analyse-Skripten (z. B. Google Analytics, Meta Pixel).
41. Verknüpfung jedes Skripts mit einer Zustimmungskategorie (z. B. marketing, statistics).
42. Rendering der Skripte nur bei expliziter Benutzerzustimmung.
43. Twig-Funktion zum Einbinden der Skripte an definierten Stellen im HTML-Dokument.

### **2.4 Optionale Service Provider**
**Erweiterte Sicherheitsfunktionen:**
44. TwoFactorServiceInterface für TOTP.
45. AuditTrailLoggerInterface für revisionssichere Protokollierung.
46. ContentSecurityPolicyBuilderInterface für dynamische CSP-Header.

---

## **3. Spezialmodule**

### **3.1 CMR-Frachtbriefgenerator**
47. Geschütztes Web-Formular zur Erfassung von Frachtbriefdaten.
48. Zugriffssteuerung über Userverwaltungsmodul.
49. Erstellung und Validierung des Formulars über Form-Builder-Service.
50. Generierung eines standardisierten CMR-Frachtbrief-PDFs zum Download.

### **3.2 Forum- und Communityfunktionen**
51. Nutzung des Userverwaltungsmoduls für Benutzerprofile, Login und Community-spezifische Rollen.
52. Definition von Domain-Entitäten: Forum, Thread, Post.
53. Controller und Views für Anzeige von Foren, Threads und Beiträgen.

---

## **4. Nicht-funktionale Anforderungen**

### **4.1 Performance**
54. TTFB für dynamische Seiten ≤ 500 ms.
55. Google Core Web Vitals im "grünen Bereich" (LCP < 2,5 Sekunden).
56. ≤ 20 SQL-Abfragen für das Rendern einer typischen Detailseite (ungecacht).
// Bis hier wurde wegen fehlender Implementierung/Testbarkeit übersprungen

57. Minifizierung und Bündelung von CSS/JS-Dateien.
> Mantis: 0000033

### **4.2 Sicherheit**
58. Mehrschichtige Sicherheit (Defense in Depth) gegen OWASP Top 10.
> Mantis: 0000034

59. Passwort-Speicherung ausschließlich mit `PASSWORD_ARGON2ID`.
> Mantis: 0000035

60. Zugriffskontrolle nach "Deny by Default".
> Mantis: 0000036

61. HTTPS-Erzwingung im Produktionsmodus.
> Die Anforderung ist erfüllt.

62. Regelmäßige Überprüfung externer Abhängigkeiten auf Sicherheitslücken.
> Mantis: 0000037

63. Secret Management über Environment-Variablen.
> Die Anforderung ist erfüllt.

### **4.3 Skalierbarkeit**
64. Stateless Application für horizontale Skalierbarkeit.
> Mantis: 0000038

65. Zentralisierte Zustandsverwaltung für Sessions (Redis, DB) und Dateiuploads (NFS, S3).
> Mantis: 0000039

66. Lose Kopplung für ressourcenintensive Aufgaben (Queues).
> Mantis: 0000040

### **4.4 Wartbarkeit**
67. Einhaltung der Coding Guidelines (PSR-12, SOLID, DDD).
> Mantis: 0000041

68. Modulare und entkoppelte Architektur.
> Mantis: 0000042

69. Automatisierte Tests (Unit/Integration) mit Code-Coverage > 90 % für Domain-Logik.
> Mantis: 0000043

70. Dokumentation als Markdown im Repository (`/docs`).
> Die Anforderung ist erfüllt.
>> Ich glaube DAS nicht!

71. Standardisierte Prozesse für Versionierung und Deployment.
> Mantis: 0000044

### **4.5 Usability**
- **Für Entwickler:**
  73. Klare Schnittstellen (APIs) mit DocBlocks.
  > Mantis: 0000045

  74. Debugging-Unterstützung über Tracy.
  > Die Anforderung ist erfüllt.

  75. Umfassende technische Dokumentation.
  > Die Anforderung ist erfüllt.

- **Für Endnutzer:**
  76. Responsive Design für alle Bildschirmgrößen.
  > Die Anforderung ist erfüllt.

  77. Barrierefreiheit nach WCAG 2.1 AA.
  > Mantis: 0000046

  78. Konsistente Bedienung und Anordnung von UI-Elementen.
  > Die Anforderung ist erfüllt.

  79. Umfassendes System-Feedback (Ladeindikatoren, Erfolgs-/Fehlermeldungen).
  > Mantis: 0000047

---

## **5. Schnittstellen und Integrationen**

### **5.1 Datenbank-Schnittstellen**
80. Repository Pattern für Datenzugriff.
> Mantis: 0000048 -> Blockiert Mantis: 0000050

81. PDO mit Prepared Statements.
> **Frage bezieht sich auf "Not yet implemented" (nyi) Functionality**
> Mantis: 0000049
> Die Anforderung ist nicht erfüllt.
> Im aktuellen Code existiert keine Datenbankanbindung, weshalb auch kein PDO oder Prepared Statements zum Einsatz kommen.
> Abweichungs-Details:
> Kein PDO-Service: In der config/services.php ist kein Service für eine Datenbankverbindung (z. B. PDO Klasse) definiert.
> Fehlende Implementierung: Es gibt keine Repository-Klassen, die Datenbankabfragen ausführen könnten.
> Dateibasierte Speicherung: Statt einer Datenbank nutzen alle aktuellen Komponenten das Dateisystem:
> Konfiguration: ConfigService nutzt config/modules.json.
> Inhalte: PageManagerService nutzt HTML-Dateien in public/pages/.
> User: AuthenticationService nutzt Daten aus der .env-Datei.

82. Domänenspezifische API für Datenzugriff (z. B. `findUserById(int $id)`).
> Mantis: 0000050 -> Abhängig von Mantis: 0000048
> Die Anforderung ist nicht erfüllt.
> Da das Repository-Pattern (Punkt 80) nicht implementiert ist, fehlt auch die darauf aufbauende domänenspezifische API.
> Abweichungs-Details:
> Keine typisierten Methoden: Es gibt keine Interfaces (wie UserRepositoryInterface), die Methoden wie findUserById(int $id) oder saveUser(User $user) definieren würden.
> Direkter Zugriff: Der Datenzugriff erfolgt, wie in den vorherigen Punkten beschrieben, "roh" über Dateisystem-Funktionen oder .env-Zugriffe innerhalb der Services. Es gibt keine Abstraktionsschicht, die fachliche Methoden bereitstellt.

### **5.2 Externe Datenschnittstellen (Import/Export)**
83. Adapter- und Transformationsschicht für externe Datenformate (CSV, TSV).
> Mantis: 0000051

84. Konfigurierbarer Importprozess mit Mapping-Konfigurationen.
> Mantis: 0000052

85. Bidirektionale Transformation für Export.
> Mantis: 0000053

### **5.3 Authentifizierungs- und Autorisierungsdienste**
86. Flexible Authentication Provider-Architektur.
> Mantis: 0000054

87. Unterstützung für LDAP/Active Directory, OAuth 2.0, SAML.
> Mantis: 0000055

88. Just-in-Time Provisioning für externe Benutzer.
> Mantis: 0000056

### **5.4 REST-API**
89. Standardisierte REST-API mit JSON-Datenaustausch.
> Mantis: 0000057

90. Authentifizierung über API-Tokens (Bearer Tokens).
> Mantis: 0000058

91. Versionierung der API (z. B. `/api/v1/`).
> Mantis: 0000059

92. Webhooks für Echtzeit-Benachrichtigungen.
> Mantis: 0000060

---

## **6. Design-, Barrierefreiheits- und Usability-Vorgaben**

### **6.1 Grundlegendes Designsystem und Theming**
93. Zentrales Designsystem mit nativem CSS (keine Präprozessoren).
> Die Anforderung ist erfüllt.
> Das Projekt setzt auf ein zentrales Design-System, das auf CSS Custom Properties (Variablen) basiert. Diese sind zentral in der Datei public/css/variables.css definiert. Es werden keine Präprozessoren (wie Sass oder LESS) verwendet; stattdessen kommen native CSS-Features zum Einsatz, die über Vite gebündelt werden.

94. BEM-Methodik für Klassennamen.
> Mantis: 0000061

95. Theming-Architektur über CSS Custom Properties.
> Die Anforderung ist erfüllt.
> Die Theming-Architektur basiert vollständig auf CSS Custom Properties (Variablen).
> - Definition: In public/css/variables.css werden globale Paletten-Variablen (z. B. --col-blue-main) und semantische Theme-Variablen (z. B. --bg-body) definiert.
> - Umschaltung: Ein Dark Mode wird durch das Überschreiben dieser Variablen unter dem Selektor [data-theme="dark"] realisiert.
> - Nutzung: Die Komponenten (in base.css etc.) verwenden ausschließlich die semantischen Variablen via var(...), was einen konsistenten Theme-Wechsel zur Laufzeit ermöglicht.

96. Modul-spezifisches CSS mit striktem Prefixing.
> Mantis: 0000062

### **6.2 Barrierefreiheit (Accessibility)**
97. WCAG 2.1 AA-Konformität.
> Mantis: 0000063

98. Tastaturbedienbarkeit für alle interaktiven Elemente.
> Mantis: 0000064

99. Semantisches HTML und ARIA-Landmarks.
> Mantis: 0000065

100. Farbkontraste: 4.5:1 für Text, 3:1 für großen Text.
> Todo: Tool für Kontrastmessung finden.

### **6.3 UI-Komponenten**
101. **Header:** Logo, Light-/Darkmode-Umschalter, Sprachauswahl, Login/Registrierung.
> Die Komponente ist vorhanden.

102. **Subheader:** Navigation mit Browsertabs, Dropdown-Unterstützung.
> Die Komponente ist vorhanden.

103. **Content-Bereich:** Lesbare Breite, neutrale Hintergrundfarbe, visuelle Abgrenzung.
> Die Komponente ist vorhanden.

104. **Footer:** Copyright, Impressum, Datenschutz, Sitemap, Social-Media-Icons.
> Die Komponente ist vorhanden.

105. **Cookie-Banner:** Zentral, modal, blockierend, mit Zustimmungsoptionen.
> Die Komponente ist vorhanden.

---

## **7. Rahmenbedingungen**

### **7.1 Gesetzliche und Regulatorische Anforderungen**
106. DSGVO/TDDDG: Privacy by Design, Datenminimierung, Cookie-Consent.
> Die Anforderung ist erfüllt.
> Das System setzt "Privacy by Design" und die DSGVO-Vorgaben technisch konsequent um:
> - Cookie-Consent: Ein Opt-in-Banner ist implementiert (templates/partials/cookie_banner.html.twig), das standardmäßig keine Cookies setzt, bis der Nutzer explizit zustimmt.
> - Widerruf: Die Entscheidung kann jederzeit über den Link "Cookie-Einstellungen" im Footer widerrufen oder geändert werden.
> - Datenminimierung: Die Zustimmung wird primär in der Session (ConsentService) und lokal (localStorage) gespeichert, ohne unnötige Nutzerprofile in einer Datenbank anzulegen. Externe Tracker oder CDNs sind im Basis-Template nicht eingebunden .

107. BFSG: Barrierefreiheit nach WCAG 2.1 AA.
> Mantis: 0000066

108. Serverstandort: EU (bevorzugt Deutschland).
> Die Anforderung ist erfüllt.
> Als Serverbetreiber wir all-inkl.com genutzt, die genutzten Server stehen ausschließlich in Deutschland.

### **7.2 Standards und Normen**
109. IT-Sicherheit: BSI IT-Grundschutz, ISO 27001.
> Mantis: 0000067

110. Lizenzrecht: Nur kompatible Lizenzen (MIT, Apache 2.0, BSD).
> Die Anforderung ist erfüllt.
> Alle über composer.json und package.json eingebundenen externen Bibliotheken (Symfony-Komponenten, Twig, Tracy, PHPUnit, Vite) unterliegen kompatiblen Open-Source-Lizenzen (MIT oder BSD-3-Clause). Es werden keine Bibliotheken mit viralen Copyleft-Lizenzen (wie GPL) oder proprietären Lizenzen verwendet.

### **7.3 Hosting und Betrieb**
111. Systemvoraussetzungen: PHP 8.2+, MySQL/PostgreSQL, Nginx/Apache.
> Mantis: 0000068

112. Backup: Dateisystem + SQL-Dump.
> Mantis: 0000069

113. Wartungsmodus: Konfigurierbare "Wartungsarbeiten"-Seite.
> Mantis: 0000070

### **7.4 Umgebungs-spezifische Konfiguration**
114. Steuerung über Environment-Variablen (z. B. `APP_ENV`).
> Die Anforderung ist erfüllt.
> Die Steuerung erfolgt zentral über die Variable APP_ENV, die in public/index.php ausgelesen wird. Sie steuert unter anderem:
> - HTTPS-Erzwingung: Nur im production-Modus aktiv.
> - Debugging (Tracy): Aktivierung des Debuggers nur im development-Modus.
> - Fehlerbehandlung: Detaillierte Exceptions im Development vs. Fehlerseiten im Production-Mode im Kernel.
> - Twig-Konfiguration: Caching und Debug-Optionen werden in config/services.php abhängig von APP_ENV gesetzt.

115. Fail-Safe-Prinzip: Default zu Production-Modus.
> Die Anforderung ist erfüllt.
> Das System implementiert ein "Fail-Safe", indem es den Umgebungsparameter $appEnv in der public/index.php standardmäßig auf 'production' setzt, falls die Environment-Variable APP_ENV nicht explizit definiert ist oder nicht geladen werden kann. Dadurch läuft die Anwendung im Zweifelsfall immer im sicheren Produktionsmodus (kein Debug-Output).

116. `.env`-Datei für lokale Entwicklung, nie im Repository.
> Die Anforderung ist erfüllt.
> Die Datei .env ist explizit in der .gitignore-Datei aufgeführt und wird somit nicht in das Repository übertragen.

---

## **8. Qualitätssicherung und Testkonzept**

### **8.1 Maßnahmen zur Sicherstellung der Softwarequalität**
117. Coding Guidelines: PSR-12, SOLID, DDD, statische Analyse mit PHPStan/Psalm.
> Mantis: 0000071

118. Teststrategie: Unit- und Integrationstests mit PHPUnit, Code-Coverage > 90 %.
> Mantis: 0000072

119. Dokumentation: DocBlocks, ADRs, technische Dokumentation im Repository.
> Mantis: 0000073
> Die Anforderung ist momentan erfüllt. Allerdings fehlen noch fundamentale Anforderungsimplementierungen die ein Rewrite der Dokumente notwendig macht.
> Das Projekt setzt umfassende Dokumentationsmaßnahmen um:
> DocBlocks: Klassen und Methoden sind konsequent mit PHPDoc-Kommentaren (@param, @return) versehen (z. B. in src/Kernel/Kernel.php oder src/Service/ConsentService.php).
> ADRs: Architekturentscheidungen werden in docs/architecture/ nummeriert festgehalten (z. B. 011-Konfiguration der Template-Engine...) und im Code referenziert (z. B. // Umsetzung ADR 011 in config/services.php).
> Technische Doku: Eine strukturierte Entwicklerdokumentation liegt unter docs/documentation/development.md und docs/architecture/ vor.

### **8.2 Teststrategie und Automatisierung**
120. Automatisierte Test-Suite für alle Kernkomponenten.
> Mantis: 0000074

121. CI/CD-Pipeline mit Linting, Tests, Build und Deployment.
> Mantis: 0000075

---
> Abschnitt 9 Wird wegen nicht erreichtem Meilenstein übersprungen
## **9. Abnahmekriterien und Inbetriebnahme**

### **9.1 Formale Abnahmekriterien**
122. Vollständige Implementierung aller funktionalen Anforderungen.
> Übersprungen.

123. Erfüllung aller nicht-funktionalen Anforderungen (Performance, Sicherheit, Skalierbarkeit).
> Übersprungen.

124. Erfolgreiche Test-Suite, keine bekannten Bugs.
> Übersprungen.

125. Vollständige Dokumentation.
> Übersprungen.

### **9.2 Prozess der Inbetriebnahme (Go-Live)**
126. Bereitstellung der Produktivumgebung.
> Übersprungen.

127. Finales Deployment über CI/CD-Pipeline.
> Übersprungen.

128. Konfiguration der Environment-Variablen.
> Übersprungen.

129. Smoke-Test und formale Übergabe.
> Übersprungen.

---

## **10. Projektorganisation und Meilensteine**

### **10.1 Rollen und Verantwortlichkeiten**
130. **Auftraggeber:** Fachliche Anforderungen, Priorisierung, Abnahme.
> Die Anforderung ist in Arbeit.

131. **Auftragnehmer:** Technische Konzeption, Implementierung, Qualitätssicherung.
> Die Anforderung ist in Arbeit.

### **10.2 Entwicklungsmeilensteine**
132. **Version 0.1.0:** Grundgerüst und Rendering-Pipeline.
> Die Anforderung ist erfüllt.

133. **Version 0.2.0:** Sichere Session-Verwaltung und Konfiguration.
> Die Anforderung ist teilweise erfüllt.
> Abweichungs-Details: Mangelnde Session-Sicherheit: Der SessionService nutzt PHP-Standard-Sessions ohne explizite Härtung im Code (z. B. fehlen session_set_cookie_params für SameSite, Secure, HttpOnly oder use_strict_mode). Die Sicherheit verlässt sich allein auf Server-Defaults.

134. **Version 0.3.0:** DSGVO- und Compliance-Mechanismen.
> Die Anforderung ist erfüllt.

135. **Version 0.4.0:** Internationalisierung (i18n).
> Die Anforderung ist erfüllt.

136. **Version 0.5.0:** Umgebungs-Handling, Logging, Sicherheits-Härtung.
> Die Anforderung ist teilweise erfüllt.
> Logging - Teilweise erfüllt: Tracy ist integriert und loggt kritische Fehler und Fingerprint-Mismatches (Debugger::log). ABER: Es fehlt die strukturierte Protokollierung (JSON) für alle sicherheitsrelevanten Ereignisse wie Session-Migration und Invalidation, wie gefordert (siehe Punkt 14)
> Session-Fixation-Schutz (Fingerprint): Der Fingerprint im SessionService verwendet den kompletten, gehashten User-Agent-String, was die Session-Validität unnötig empfindlich macht und von der Forderung nach der Extraktion von Browser-Familie und OS-Plattform abweicht (siehe Punkt 6).
> Kein CSRF-Schutz: Der Mechanismus zur automatischen Generierung und Validierung von CSRF-Tokens fehlt vollständig (siehe Punkt 13).
> Cookies: Es wird nicht das empfohlene __Host- Präfix für Cookies verwendet, und es wird der Standard-Name PHPSESSID beibehalten (siehe Punkt 11).
> Session-ID-Entropie: Es ist keine explizite Generierung der Session-ID mit 128 Bit Entropie über random_bytes() implementiert; stattdessen wird auf PHP-Defaults vertraut (siehe Punkt 5).
> Zugriffskontrolle: Es fehlt eine zentrale "Deny by Default"-Sicherheitskontrolle (siehe Punkt 60).

137. **Version 0.6.0:** Standard-Layout und UI-Komponenten.
> Die Anforderung ist erfüllt.

138. **Version 0.7.0:** Volle Funktionalität der Standardseiten.
> Die Anforderung ist teilweise erfüllt.
> Obwohl alle notwendigen Standardseiten (/, /impressum, /datenschutz, /kontakt) und der Admin-Bereich (/admin) mit funktionierendem Content-Rendering (Twig, Controller) implementiert sind, wird der Anspruch auf "Volle Funktionalität" aufgrund eines kritischen Barrierefreiheitsmangels bei den interaktiven UI-Elementen nicht erfüllt.
> Abweichungs-Details:
> Fehlende Tastaturbedienbarkeit bei Dropdowns (Verstoß gegen Anforderung 98): Die Sprachauswahl und das Benutzer-Dropdown im Header (templates/base.html.twig) werden ausschließlich über den CSS-Selektor :hover eingeblendet.
> Folge: Tastaturnutzer (die den Toggle-Button mit der Tab-Taste fokussieren) können das Menü nicht öffnen, da der :hover-Zustand bei Tastaturbedienung fehlt. Die in diesen Menüs enthaltenen Funktionen (z. B. Sprachwechsel, Logout) sind somit für Tastaturnutzer nicht funktional.
> Fehlender CSRF-Schutz (Verstoß gegen Anforderung 13): Die Formulare (wie der Login zum Admin-Bereich) sind nicht durch automatische CSRF-Tokens geschützt, was die Sicherheit (ein integraler Bestandteil der Funktionalität) des Standard-Logins kompromittiert.

139. **Version 0.8.0:** Test-Suite, CI-Pipeline, Entwicklerdokumentation.
> Die Anforderung ist teilweise erfüllt.
> Test-Suite - Nicht erfüllt:	Code-Coverage fehlt: Die CI-Pipeline ist explizit so konfiguriert, dass sie keine Code-Coverage misst oder durchsetzt (coverage: none). Die geforderten 90 % sind nicht nachweisbar (siehe Punkt 69).
> CI-Pipeline -	Nicht erfüllt:	Deployment fehlt: Die Pipeline ist eine reine Continuous Integration (Build & Test) und enthält keinen Continuous Deployment-Schritt, der die Anwendung ausliefert (siehe Punkt 121).
> Mangelnde Testabdeckung - Nicht erfüllt: Die Test-Suite deckt nur Teile der Services ab, aber zentrale Komponenten wie den Kernel, das Routing und die Controller sind ungetestet (siehe Punkt 120).
> Fehlende Qualitätswerkzeuge - Nicht erfüllt: Es fehlen die Werkzeuge für die statische Analyse (PHPStan/Psalm) und den Code-Stil-Fixer (PHP-CS-Fixer), die zur Qualitätssicherung gehören (siehe Punkt 117).

140. **Version 0.9.0:** Stabilisierung und Abnahme.
> Meilenstein ist nicht erreicht.

141. **Version 1.0.0:** Produktivversion (fehlerfrei, abgenommen).
> Meilenstein ist nicht erreicht.

---

## **11. Dokumentations-Artefakte**
142. **Entwicklerdokumentation:** Installation, Architektur, Konfiguration, Kern-Services, Testing.
> Die Anforderung ist momentan erfüllt. Allerdings fehlen noch fundamentale Anforderungsimplementierungen die ein Rewrite der Dokumente notwendig macht.

143. **Quellcode-Dokumentation:** DocBlocks für öffentliche Klassen/Methoden.
> Die Anforderung ist momentan erfüllt. Allerdings fehlen noch fundamentale Anforderungsimplementierungen die eine erneute Prüfung notwendig machen.
144. **ADRs:** Wichtige Architekturentscheidungen im `/docs/adr`-Verzeichnis.
> Die Anforderung ist momentan erfüllt. Allerdings fehlen noch fundamentale Anforderungsimplementierungen die eine erneute Prüfung notwendig machen.

---
