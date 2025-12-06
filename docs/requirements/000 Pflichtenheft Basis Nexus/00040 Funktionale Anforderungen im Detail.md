## 4. Funktionale Anforderungen im Detail
`(Spezifikation der einzelnen Funktionen, unterteilt in die im Lastenheft genannten Kategorien.)`

### 4.1. Basisframework (Core Application Scaffolding)
Das Basisframework enthält ausschließlich die Komponenten, die für den Betrieb einer sicheren, performanten und DSGVO-konformen Webanwendung zwingend erforderlich sind, ohne jegliche anwendungsspezifische Logik wie eine Benutzer- oder Inhaltsverwaltung. Es ist die technische Grundlage, auf der alle späteren Module aufsetzen.

#### 4.1.1. Seiten-Rendering und Template-Management
Spezifikation: Das Framework stellt einen Mechanismus bereit, um Anfragen über die Routing-Komponente (siehe 3.3.2) an einen Controller zu leiten, der ein Twig-Template (siehe 3.3.3) rendert und als HTML-Seite ausliefert. Es existiert keine datenbankgestützte Inhaltsverwaltung im Basisframework. Die Templates selbst sind flexibel und können von späteren Modulen dynamisch mit Inhalten befüllt werden.

Begründung: Erfüllt die Kernanforderung, statische und templetisierte HTML-Seiten anzeigen zu können. Dies bildet das Fundament für jede Art von Web-Anwendung, von der einfachen Landing-Page bis zum komplexen Portal.

#### 4.1.2. Sichere Session-Verwaltung (Service-Spezifikation)

Spezifikation: Die Session-Verwaltung ist eine kritische Sicherheitskomponente des Basisframeworks. Sie darf nicht als einfacher Wrapper um die globale $_SESSION-Variable implementiert sein, sondern muss als objektorientierter Service (SessionService) realisiert werden, der Daten strukturiert, isoliert und schützt. Dieser Service gewährleistet eine saubere Isolation, Manipulationssicherheit, Austauschbarkeit des Speichers und vollständige Kontrolle über Laufzeiten, Fingerprinting und Sicherheitsmechanismen (Session-Hijacking,  Fixation-Schutz u.a.). Die folgenden Anforderungen definieren den verbindlichen Mindeststandard für Implementierungen.

Architekturgrundsätze

Der SessionService arbeitet vollständig unabhängig vom Anwendungscode, definiert strukturierte “Attribute Bags” als isolierte logische Container und nutzt das native SessionHandlerInterface, um den Speichermotor (Datei, Redis, DB) ohne Codeänderungen austauschbar zu machen. Eine Session repräsentiert niemals den Authentifizierungsstatus eines Nutzers; Login-Zustände liegen ausschließlich im Security-Bag und werden nur vom AuthenticationService verwaltet.

Die Session-ID muss mindestens 128 Bit Entropie aufweisen (z. B. 32 Zeichen bei Base64-Codierung). Es wird `random_bytes()` für die Generierung genutzt, um kryptografisch sichere Zufallswerte zu gewährleisten. `$sessionId = bin2hex(random_bytes(32));`

Verbindliche Anforderungen:

- Attribute Bags (Daten-Isolation):
  - Um Kollisionen zwischen verschiedenen Modulen zu verhindern, muss der Service Session-Daten in logisch getrennten Containern ("Bags") verwalten:
  - Security Bag: Reservierter Bereich für Authentifizierungsdaten (User-ID, RBAC-Snapshot) inklusive einer versionsbasierten Kennung (security_version), die bei jedem Login erhöht wird, um parallele veraltete Sessions automatisch zu invalidieren. Damit werden Replay-Angriffe bei veralteten Sessions ausgeschlossen. Schreibzugriff darf nur durch den AuthenticationService erfolgen.
  - Attribute Bag: Bereich für allgemeine Benutzer-Einstellungen (z.B. Sprache, Darkmode-Präferenz) sowie für anwendungsbezogene Zähler wie Login-Rate-Limits, um z.B. Login-Bruteforce abzufangen.
  - Flash Bag: Bereich für temporäre Nachrichten (Erfolg/Fehler), die nach dem einmaligen Auslesen automatisch gelöscht werden ("Auto-Expire"), auch tab-sicher ohne unbeabsichtigte Mehrfach-Löschung. Der Service sorgt für race-condition-freies Speichern und Auslesen durch atomare Schreiboperationen über den SessionHandler.

- Sicherheits-Methoden:
  - Migration: Die Methode `regenerateId()` muss implementiert sein und zwingend bei jeder Änderung des Privilegien-Levels (Login, Logout, Passwortänderungen, Rollenwechsel oder sensiblen Transaktionen (z. B. Zahlungen) etc.) ausgeführt werden, um Session-Fixation-Angriffe zu unterbinden. Die Methode `regenerateId()` darf lediglich die Session-ID rotieren, nicht die Cookie-Lebensdauer unerlaubt verlängern.
  - Invalidate: Eine Methode invalidate() muss existieren, die die Session serverseitig zerstört und das Session-Cookie clientseitig ungültig macht, sie setzt alle Bags zurück und protokolliert den Vorgang. 
  - Fingerprinting: Der Service muss beim Start einen Hash aus
   1. Browser-Familie
   2. Major-Version
   3. Betriebssystem-Plattform
   4. Anonymisierter IP-Adresse (IPv4 /24 und IPv6 /64)
   5. Optional (wegen Datenschutz) kann die ungefähre Geolokation (z. B. Land/Region) in den Fingerprint einbezogen werden, um ungewöhnliche Standorte zu erkennen. Falls genutzt wird aus Datenschutzgründen die Geolokation nur als Hash gespeichert.
   6. Es wird kein gehashter UA-String genutzt, da dieser manipuliert sein könnte.
   bilden und bei jedem Request prüfen. Bei Abweichung wird die Session invalidiert. Der Vergleich erfolgt zeitkonstant (hash_equals()), um Timing-Leaks zu vermeiden. Das Fingerprinting muss konfigurierbar deaktivierbar sein (z. B. für Geräte mit wechselnden Mobilnetzen).
  - Anti-Replay: Neben der Session-ID muss ein serverseitiges Nonce/Tokensystem im Security-Bag existieren.
  - Bei Login wird security_version erhöht. Ältere Sessions desselben Nutzers werden dadurch ungültig. Jede aktive Session eines Nutzers muss anhand der security_version global invalidierbar sein.

- Storage-Abstraktion:
  - Der Service muss auf dem nativen SessionHandlerInterface basieren. Dies stellt sicher, dass der Speicherort der Sessions bei Bedarf (z.B. für Loadbalancing) vom Dateisystem auf Redis oder eine Datenbank umgestellt werden kann, ohne den Applikationscode anzupassen.
  - Datenschutz: Es ist sicherzustellen, dass der Speicherdienst (Redis/DB) durch Infrastruktur-Maßnahmen (TLS, Private Network, Passwort/ACL) gegen unbefugten Zugriff gesichert ist.
  - Der Serializer darf nicht PHP-serialize verwenden. JSON ist verpflichtend, um Unserialize-Injections auszuschließen.
  - Schreiboperationen müssen atomar erfolgen. 
  
  Bei Redis Nutztung
  - sind entsprechende Lock-Mechanismen (SET NX + Expire) sicherzustellen.
  - ist die Redis-Authentifizierung und TLS-Verschlüsselung für die Verbindung zu verwenden.
  - Der `protect-mode` muss in Redis auf `yes` gesetzt sein, um unbefugten Zugriff zu verhindern.

- Cookie-Konfiguration:
  - Die Session-Cookies müssen zentral konfigurierbar sein und standardmäßig die sichersten Flags nutzen: HttpOnly (kein JS-Zugriff), Secure (nur HTTPS) und SameSite=Strict (CSRF-Schutz).
  - Name Obfuscation: Der Name des Session-Cookies darf nicht PHPSESSID lauten. Er muss konfigurierbar sein oder standardmäßig einen zufälligen Hash-Wert (z.B. Präfix + Random String) verwenden, um Framework-Fingerprinting zu erschweren.
  - Es wird empfohlen das Präfix auf `__Host` zu setzen um sicherzustellen, dass sie nur für die aktuelle Domain gelten und nicht von Subdomains ausgelesen werden können.
  - Sessions dürfen niemals über URL-Parameter übertragen werden; use_trans_sid muss deaktiviert sein. `session.use_trans_sid = 0`
  - Session-ID darf niemals via GET/POST akzeptiert werden.
  - In Sonderfällen (z. B. Single-Sign-On) darf "SameSite=Lax" genutzt werden. Dies sollte konfigurierbar sein oder Systemseitig erkannt werden.

- Lifetime-Management & Garbage Collection:
  - Strict Idle Timeout: Der Service muss den Zeitstempel des letzten Zugriffs (last_activity) in der Session speichern. Überschreitet die Inaktivität einen definierten Wert (z.B. 30 Minuten), muss die Session beim nächsten Request aktiv invalidiert werden, unabhängig von der PHP-internen Garbage Collection.
  - Absolute Max Lifetime: Optional muss eine absolute Obergrenze konfigurierbar sein (z.B. 12 Stunden), nach der eine Session zwangsweise beendet wird, auch bei Aktivität.
  - Cookie Lifetime: Die Lebensdauer des Session-Cookies muss synchron zum Idle-Timeout konfiguriert sein (typischerweise 0 = Browser-Session oder identisch zum Timeout).
  - Der SessionService darf nicht darauf vertrauen, dass PHPs Garbage-Collection zuverlässig läuft; alle Prüfungen müssen explizit durch den Service erfolgen.

- CSRF-Schutz:
  - Das CSRF-Token wird vom SessionService automatisch erzeugt.
  - Der SessionService verwaltet das stabil an die Session gekoppelte CSRF-Token.
  - Per-Formular-Tokens (Double-Submit) müssen unterstützt werden.
  - Alle Tokens müssen im Security Bag liegen.

- Ereignisprotokollierung:
  - Der Service muss sicherheitsrelevante Aktionen in strukturierter Form (JSON) loggen, darunter Session-Migration, Session-Invalidation, Timeout-Invalidation, Fingerprint-Mismatch, security_version-Konflikte, CSRF-Token-Fehler sowie ungewöhnliche Session-ID-Wiederverwendungen.
  - Zu jeder Sicherheitsrelevanten Aktion werden folgende Metadaten geloggt: IP-Adresse (anonymisiert), User-Agent, Zeitstempel und Session-ID (gehasht).
  - Der SessionService muss "ungewöhnliche Muster" (Anomalien) erkennen können. Hierzu Zählen bspw. mehrere Session-Regenerationen in kurzer Zeit, weitere Anomalien sind in "00040.1 Anhang Anomalien.md" aufgeführt.

- Multi-Tab- & Race-Condition-Sicherheit:
  - Es wird ein Timestamp `$_SESSION['_version'] = time();` in der Session verwendet, um Race Conditions zu erkennen. Vor jedem Schreibzugriff wird geprüft ob die Version noch aktuell ist.
  - Flash Messages müssen tab-sicher sein. Dafür werden unique IDs für Flash-Messages genutzt, um sicherzustellen, dass sie nur einmal pro Tab angezeigt werden.
  - Parallele Requests dürfen sich nicht gegenseitig überschreiben. 
  - Atomare Schreibvorgänge müssen sichergestellt sein.

- Kontextsteuerung:
  - Im CLI-Kontext darf keine Session automatisch geöffnet werden.
  - Web-APIs, Controller oder UseCases dürfen Sessions nur per explizitem Opt-In aktivieren.
  - Der SessionService muss global deaktivierbar sein (z.B. für APIs oder CLI-Skripte).
  - Es werden umgebungsbezogene (Dev, Staging, Prod) Einstellungen verwendet um z.B. Fingerprinting nur in Produktion aktivieren.

#### 4.1.3. Internationalisierung (i18n)
Spezifikation: Das Framework stellt einen zentralen "Translator"-Service bereit. Dieser Service implementiert eine definierte Schnittstelle (TranslatorInterface).
- Im Basisframework wird dieser Service mit einem Provider konfiguriert, der Sprachstrings aus einfachen PHP-Dateien (/translations/de.php, /translations/en.php) lädt.
- Die Schnittstelle ist so konzipiert, dass spätere Module eigene Provider (z.B. einen DatabaseTranslationProvider) registrieren können, die ihre Übersetzungen aus Datenbanktabellen laden. Das System aggregiert dann die Übersetzungen aus allen Quellen.

Begründung: Schafft eine flexible und erweiterbare Lösung für die Mehrsprachigkeit, die von einfachen Konfigurationsdateien bis hin zu komplexen, datenbankgestützten Übersetzungsmodulen skaliert.

#### 4.1.4. DSGVO- und Compliance-Mechanismen
Spezifikation: Das Basisframework enthält einen zentralen Service zur Verwaltung von Benutzerzustimmungen (Cookie-Consent). Dieser Service stellt Funktionen bereit, um den Status der Zustimmung abzufragen und zu speichern (unter Nutzung des Session-Services aus 4.1.2). Ein rudimentäres Cookie-Banner (HTML/JS), dessen Inhalte über die i18n-Komponente (4.1.3) verwaltet werden, ist Teil des Basisframeworks.

Begründung: Gewährleistet, dass jede auf "Nexus" basierende Anwendung die grundlegenden gesetzlichen Anforderungen an den Datenschutz von Beginn an erfüllen kann.

#### 4.1.5. Performance- und Sicherheitsgrundlagen
Spezifikation: Beinhaltet die bereits in der Architektur (Kapitel 3) und den Coding Guidelines (Kapitel 9) definierten Grundlagen wie Schutz vor XSS, CSRF (Token-Generierung ist im Session-Service verankert), SQL-Injection, einheitliches Logging mit Tracy und eine klare Konfiguration für Caching-Mechanismen (Opcode-Caching).

Begründung: Stellt sicher, dass das Fundament des Frameworks den nicht-funktionalen Anforderungen an Sicherheit und Performance genügt.

#### 4.1.6. SEO-Grundlagen (Search Engine Optimization)
Spezifikation:
Das Basisframework muss die technischen Grundvoraussetzungen für eine effektive Suchmaschinenoptimierung bereitstellen, um die Auffindbarkeit der darauf basierenden Webseiten sicherzustellen. Dies umfasst die folgenden Kernfunktionen:
- Meta-Tag-Management: Die Template-Engine und die Controller-Struktur müssen es ermöglichen, für jede gerenderte Seite einen individuellen <title>-Tag und eine <meta name="description"> zu setzen.
- Sitemap-Generierung: Das Framework muss einen Mechanismus (z.B. einen Konsolenbefehl oder einen dedizierten Controller) bereitstellen, der eine sitemap.xml-Datei gemäß dem offiziellen Standard generiert. Diese Sitemap listet alle öffentlichen Seiten der Anwendung auf.
- robots.txt-Management: Das Framework muss im /public-Verzeichnis eine standardmäßige robots.txt-Datei bereitstellen, die gängige "Good Practices" umsetzt (z.B. das Disallow von System-Verzeichnissen). Diese Datei muss anpassbar sein.
- Canonical URLs: Das Framework muss für jede Seite automatisch einen rel="canonical" Link-Tag im HTML-<head> generieren, der auf die primäre, saubere URL der jeweiligen Seite verweist, um Probleme mit Duplicate Content zu vermeiden.

Begründung:
Die Implementierung dieser On-Page-SEO-Grundlagen ist entscheidend, um die Sichtbarkeit und den Erfolg der mit Nexus erstellten Webprojekte in Suchmaschinen zu gewährleisten. Während die nicht-funktionalen Anforderungen bereits eine SEO-freundliche technische Basis schaffen, stellen diese Funktionen die notwendigen Werkzeuge zur Verfügung, um den Inhalt der Seite gezielt für Suchmaschinen aufzubereiten.

### 4.2. Erweiterungsmodule
Die hier definierten Erweiterungsmodule sind standardisierte, wiederverwendbare Funktionsblöcke, die auf dem Basisframework (4.1) aufsetzen. Sie sind optional und können je nach Anforderung der Zielanwendung aktiviert und konfiguriert werden. Sie nutzen die vom Basisframework bereitgestellten Schnittstellen und Mechanismen (z.B. Events, DI-Container).


#### 4.2.1. Userverwaltung und Rechteverwaltung
weitgehend gestrichen - siehe Pflichtenheft "Userverwaltung". Arbeitsversion in: C:\xampp\htdocs\_ConceptSnippets\251204 UserVerwaltung.docx

Spezifikation: Dieses Modul stellt eine vollständige Benutzer- und Rechteverwaltung bereit. Folgende Grundvoraussetzungen MUSS die Userverwaltung erfüllen um integriert zu werden: 
- Anwendungs-Services (in der Application-Schicht) für die grundlegenden Prozesse: Benutzerregistrierung, Logout und Passwort-Reset.
- Der Login-Prozess muss die Authentifizierung des Benutzers sowohl über die Kombination Benutzername + Passwort als auch über E-Mail-Adresse + Passwort ermöglichen.
- Ein kontextbezogenes, rollenbasiertes Zugriffskontrollsystem (RBAC). Die Berechtigungen eines Benutzers leiten sich immer aus der Kombination seiner Gruppe und seiner Rolle ab. Dies ermöglicht es, dass ein Benutzer in Gruppe "A" die Rolle "Admin" haben kann, während ein anderer Benutzer in Gruppe "B" ebenfalls die Rolle "Admin" mit potenziell anderen Rechten innehat.
- Die Implementierung der UserRepositoryInterface zur Speicherung und zum Abruf von Benutzerdaten aus der Datenbank.
- Eine nahtlose Integration in den Session-Service (4.1.2) des Basisframeworks, um den Login-Status sicher zu verwalten und die Session-ID nach dem Login zu regenerieren.

Begründung: Dies ist eine der zentralen Anforderungen aus dem Lastenheft, um die "dezentral organisierte Userverwaltung" des IST-Zustands abzulösen. Die Hinzunahme der Gruppe ermöglicht eine flexible Rechtevergabe pro Kontext, ohne die Komplexität einer vollwertigen Mandantenfähigkeit zu benötigen. Die Möglichkeit, sich mit Benutzername oder E-Mail-Adresse anzumelden, erhöht die Benutzerfreundlichkeit.

#### 4.2.2. Formular- und Datenmanagement (eigenständiges Modul)
Todo: Pflichtenheft Formular- und Datenmanagement erstellen. - siehe Pflichtenheft "Formular- und Datenmanagement". Arbeitsversion in: 

Hier der Originaltext als Basis für das separate Dokument:

Abhängigkeiten: Dieses Modul ist eine eigenständige Erweiterung und setzt ausschließlich auf dem Nexus-Basisframework (PH: 4.1) auf. Es besteht keine Abhängigkeit zu weiteren Modulen.
Spezifikation: Das Modul bietet eine zentrale Lösung für die Erstellung, Validierung und Verarbeitung von Web-Formularen sowie die Generierung von Dokumenten. Es umfasst:
•	Einen Form-Builder-Service, der es Entwicklern ermöglicht, Formulare programmgesteuert zu definieren (Felder, Typen, Labels, Validierungsregeln).
Einen Validierungs-Service, der serverseitige Überprüfungen von Formulareingaben durchführt (z.B. Pflichtfelder, E-Mail-Format, Längenbeschränkungen).
•	Eine Integration in die Template-Engine Twig, um die Formulare einfach im Frontend rendern zu können, inklusive der Anzeige von Validierungsfehlern.
•	Einen PDF-Generierungs-Service. Dieser Service stellt eine Schnittstelle bereit, um aus übergebenen Daten (z.B. validierten Formulareingaben) eine PDF-Datei zu erzeugen. Die konkrete Implementierung erfolgt über eine etablierte Bibliothek (z.B. TCPDF oder Dompdf).
•	Die automatische Integration des CSRF-Schutzes aus dem Basisframework (PH: 4.1.5) in alle erstellten Formulare, um die Sicherheit zu gewährleisten.
Begründung: Dieses Modul schafft eine standardisierte und wiederverwendbare Lösung für alle Arten von Formulareingaben, unabhängig davon, ob ein Benutzer angemeldet ist oder nicht. Es ermöglicht die einfache Umsetzung von öffentlichen Formularen, wie z.B. einem Kontaktformular, ohne dass das Userverwaltungs-Modul installiert sein muss. Gleichzeitig bildet es die technische Grundlage, um den im LH (IST-Zustand, S. 2) erwähnten "Frachtbriefgenerator" als modernes Spezialmodul (siehe PH: 4.3) neu zu implementieren und die Anforderung "Formulargenerierung und PDF-Erstellung" (LH: Anwendungsfälle, S. 5) zu erfüllen.

#### 4.2.3. Marketing-Integrationen (eigenständiges Modul)
Todo: Pflichtenheft Marketing-Integrationen erstellen. - siehe Pflichtenheft "Marketing-Integrationen". Arbeitsversion in: 

Abhängigkeiten:
Dieses Modul ist eine eigenständige Erweiterung und setzt ausschließlich auf dem Nexus-Basisframework (PH: 4.1) auf. Es besteht keine Abhängigkeit zu weiteren Modulen. Es interagiert direkt mit dem Service für Benutzerzustimmungen (PH: 4.1.4).
Spezifikation:
Das Modul stellt eine zentrale und datenschutzkonforme Schnittstelle zur Einbindung von externen Marketing- und Analyse-Skripten (z.B. Google Analytics, Google Ads Conversion Tracking, Meta Pixel) bereit. Es umfasst:
•	Einen Script-Management-Service, der es erlaubt, Skripte und Tracking-Pixel über eine Konfigurationsdatei zu registrieren.
•	Eine zwingende Verknüpfung jedes Skripts mit einer Zustimmungskategorie (z.B. marketing, statistics).
•	Der Service stellt sicher, dass Skripte nur dann im Frontend gerendert werden, wenn der Benutzer über den Consent-Manager des Basisframeworks die explizite Zustimmung für die entsprechende Kategorie erteilt hat.
•	Eine Twig-Funktion (z.B. render_tracking_scripts('head')), die im Template aufgerufen werden kann, um die freigegebenen Skripte an der korrekten Stelle im HTML-Dokument auszugeben.
Begründung:
Dieses Modul zentralisiert die Verwaltung von Drittanbieter-Skripten und stellt deren DSGVO-konforme Einbindung sicher, wie es die Anforderung „Marketing-Integrationen mit Zustimmungssteuerung“ (LH: S. 3) fordert. Es verhindert, dass Tracking-Codes unkontrolliert im Quellcode verteilt werden und koppelt die Marketing-Funktionalität sauber von der Kernanwendung ab.

#### 4.2.4. Optionale Service Provider
Zusätzlich zu den funktionalen Modulen stellt das Nexus-Ökosystem eine Reihe von optionalen "Service Providern" bereit. Hierbei handelt es sich um Pakete, die reine Backend-Funktionalität und Dienste zur Verfügung stellen, die von anderen Modulen genutzt werden können, ohne eine feste Abhängigkeit zu erzeugen.

##### 4.2.4.1. Erweiterte Sicherheitsfunktionen (Service Provider)
Todo: Pflichtenheft "Erweiterte Sicherheitsfunktionen" erstellen. - siehe Pflichtenheft "Erweiterte Sicherheitsfunktionen". Arbeitsversion in: 

Abhängigkeiten:
Dieser Service Provider setzt ausschließlich auf dem Nexus-Basisframework (PH: 4.1) auf. Er wird typischerweise in Projekten eingesetzt, die auch das User-Modul (PH: 4.2.1) verwenden, ist aber nicht fest daran gekoppelt.

Spezifikation:
Dieser Provider registriert spezialisierte Sicherheitsdienste im DI-Container der Anwendung. Andere Module können diese Dienste optional nutzen, wenn sie verfügbar sind. Enthaltene Dienste können sein:
- Ein TwoFactorServiceInterface, das die Logik für Time-based One-Time Passwords (TOTP) kapselt.
- Ein AuditTrailLoggerInterface, der eine revisionssichere Protokollierung von sicherheitsrelevanten Aktionen ermöglicht.
- Ein ContentSecurityPolicyBuilderInterface, der die dynamische Erstellung von CSP-Headern vereinfacht.

Begründung:
Durch die Kapselung dieser fortgeschrittenen Funktionen in einem optionalen Service Provider wird das Basisframework schlank gehalten. Die Funktionalität wird zentral bereitgestellt und kann von jedem Modul per Dependency Injection genutzt werden, ohne dass eine direkte Kopplung entsteht. Dies maximiert die Flexibilität und Sicherheit für High-Security-Anwendungen, wie in der Anforderung "erweiterte Security-Module" (LH: S. 3) vorgesehen.

### 4.3. Spezialmodule
Grundsatz:
Spezialmodule sind anwendungsspezifische, fakultative Module, die konkrete Fachanforderungen umsetzen. Im Gegensatz zu den generischen Erweiterungsmodulen (PH: 4.2) sind sie nicht auf maximale Wiederverwendbarkeit in unterschiedlichen Kontexten ausgelegt, sondern stellen die Implementierung der spezifischen Altanwendungen auf der neuen, einheitlichen "Nexus"-Plattform dar. Sie nutzen das Basisframework und die Erweiterungsmodule als technische Grundlage.

#### 4.3.1. CMR-Frachtbriefgenerator
Todo: Pflichtenheft "CMR-Frachtbriefgenerator" erstellen. - siehe Pflichtenheft "CMR-Frachtbriefgenerator". Arbeitsversion in: 

Abhängigkeiten:
Dieses Modul setzt auf dem Nexus-Basisframework (PH: 4.1) auf. Es erfordert die Installation der folgenden Erweiterungsmodule:
- Userverwaltung und Rechteverwaltung (PH: 4.2.1)
- Formular- und Datenmanagement (PH: 4.2.2)

Spezifikation:
Dieses Modul bildet die Funktionalität des bestehenden "Frachtbriefgenerators" (LH: IST-Zustand, S. 2) nach.
- Es stellt ein geschütztes Web-Formular zur Erfassung aller relevanten Frachtbriefdaten bereit. Der Zugriff wird über das Modul "Userverwaltung und Rechteverwaltung" gesteuert.
- Die Erstellung und Validierung des Formulars erfolgt über den Form-Builder-Service des Moduls "Formular- und Datenmanagement".
. Nach erfolgreicher Validierung werden die Formulardaten an den PDF-Generierungs-Service übergeben, um ein standardisiertes CMR-Frachtbrief-Dokument im PDF-Format zu erzeugen, das dem Benutzer zum Download angeboten wird.
- Es definiert eigene Domain-Entitäten wie Frachtbrief und Transporteur.

Begründung:
Die Neuimplementierung als Spezialmodul auf der "Nexus"-Basis stellt sicher, dass die Anwendung von zentralen Sicherheitsmechanismen, einer einheitlichen Benutzerverwaltung und standardisierten Prozessen profitiert. Dies reduziert den Wartungsaufwand und erhöht die Sicherheit im Vergleich zur alten, isolierten Lösung.

#### 4.3.2. Forum- und Communityfunktionen
Todo: Pflichtenheft "Forum- und Communityfunktionen" erstellen. - siehe Pflichtenheft "Forum- und Communityfunktionen". Arbeitsversion in: 

Abhängigkeiten:
Dieses Modul setzt auf dem Nexus-Basisframework (PH: 4.1) auf und erfordert die Installation der folgenden Erweiterungsmodule:
- Userverwaltung und Rechteverwaltung (PH: 4.2.1)
- Optional: Formular- und Datenmanagement (PH: 4.2.2) für erweiterte Editor-Funktionen.

Spezifikation:
Dieses Modul ersetzt das bestehende phpBB-Forum (LH: IST-Zustand, S. 2) durch eine native, voll integrierte Lösung.
- Es nutzt das Modul "Userverwaltung und Rechteverwaltung" als Grundlage für Benutzerprofile, Login und die Vergabe von Community-spezifischen Rollen (z.B. "Moderator", "Mitglied") innerhalb einer "Forum"-Gruppe.
- Es definiert eigene Domain-Entitäten wie Forum, Thread und Post.
- Es stellt Controller und Views für die Anzeige von Foren, die Leseansicht von Threads und das Erstellen von neuen Beiträgen und Antworten bereit.

Begründung:
Die native Integration löst das Problem der "nicht vollwertigen Anbindung" des alten Forums. Eine gemeinsame Benutzerbasis und eine einheitliche technische Plattform verbessern die User Experience, vereinfachen die Administration und schließen potenzielle Sicherheitslücken, die durch die lose Kopplung der Altsysteme entstanden sind.

#### 4.3.3. Weitere Spezialmodule (Integrationsprojekte)
Todo: Pflichtenheft für die weiteren Spezialmodule erstellen. 

Abgrenzung und Vorgehen:
Die weiteren im Lastenheft (LH: IST-Zustand, S. 2) genannten Altsysteme, insbesondere das selbst entwickelte CMS inklusive des Partnerportals, werden nicht im Rahmen der initialen Entwicklung des "Nexus"-Frameworks umgesetzt.
Aufgrund ihrer spezifischen Komplexität, potenziell abweichender Schnittstellen zu Drittsystemen und der Notwendigkeit einer Datenmigration werden diese als eigenständige, nachgelagerte Integrationsprojekte behandelt. Diese Projekte starten erst, nachdem das "Nexus"-Basisframework (PH: 4.1) sowie die generischen Erweiterungsmodule (PH: 4.2) fertiggestellt, getestet und als stabile Plattform freigegeben wurden.

Begründung:
Diese Vorgehensweise stellt sicher, dass der Fokus des Kernprojekts "Nexus" auf der Schaffung einer robusten, sicheren und gut dokumentierten technologischen Basis liegt. Die komplexen Anforderungen der spezifischen Altanwendungen werden in dedizierten Projekten mit eigenen Zeit- und Ressourcenplänen behandelt, was das Risiko für das Kernprojekt minimiert und eine qualitativ hochwertige Integration gewährleistet.

---
