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

4.2.1. Userverwaltung und Rechteverwaltung
weitgehend gestrichen - siehe Pflichtenheft "Userverwaltung". Arbeitsversion in: C:\xampp\htdocs\_ConceptSnippets\251204 UserVerwaltung.docx

Folgende Grundvoraussetzungen MUSS die Userverwaltung erfüllen um integriert zu werden: 
- Anwendungs-Services (in der Application-Schicht) für die grundlegenden Prozesse: Benutzerregistrierung, Logout und Passwort-Reset.
- Der Login-Prozess muss die Authentifizierung des Benutzers sowohl über die Kombination Benutzername + Passwort als auch über E-Mail-Adresse + Passwort ermöglichen.
- Ein kontextbezogenes, rollenbasiertes Zugriffskontrollsystem (RBAC). Die Berechtigungen eines Benutzers leiten sich immer aus der Kombination seiner Gruppe und seiner Rolle ab. Dies ermöglicht es, dass ein Benutzer in Gruppe "A" die Rolle "Admin" haben kann, während ein anderer Benutzer in Gruppe "B" ebenfalls die Rolle "Admin" mit potenziell anderen Rechten innehat.
- Die Implementierung der UserRepositoryInterface zur Speicherung und zum Abruf von Benutzerdaten aus der Datenbank.
- Eine nahtlose Integration in den Session-Service (4.1.2) des Basisframeworks, um den Login-Status sicher zu verwalten und die Session-ID nach dem Login zu regenerieren.

Begründung: Dies ist eine der zentralen Anforderungen aus dem Lastenheft, um die "dezentral organisierte Userverwaltung" des IST-Zustands abzulösen. Die Hinzunahme der Gruppe ermöglicht eine flexible Rechtevergabe pro Kontext, ohne die Komplexität einer vollwertigen Mandantenfähigkeit zu benötigen. Die Möglichkeit, sich mit Benutzername oder E-Mail-Adresse anzumelden, erhöht die Benutzerfreundlichkeit.

---
