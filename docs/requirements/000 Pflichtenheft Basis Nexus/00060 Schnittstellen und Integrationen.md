## 6. Schnittstellen und Integrationen
`(Definition aller internen und externen Schnittstellen, z.B. zu Datenbanken, Authentifizierungsdiensten und APIs.)`

Die Auflistung der benötigten Schnittstellen an dieser Stelle _kann nicht_ vollständig und abschliessend sein, später notwendige und potentiell wiederverwendbare Schnittstellen werden hier sukzessive eingepflegt.

Dieses Kapitel definiert die Architektur und die Spezifikationen für alle aktuell geforderten internen und externen Schnittstellen des "Nexus"-Frameworks. Eine klare Definition dieser Schnittstellen ist entscheidend für die im Lastenheft (LH: S. 1) geforderte Modularität, Erweiterbarkeit und Wartbarkeit des Systems.

### 6.1. Datenbank-Schnittstellen
Spezifikation:
- Abstraktion durch Repositories: Jeglicher direkter Datenbankzugriff aus der Anwendungslogik (Application-Schicht) ist untersagt. Die gesamte Datenbankkommunikation muss über das Repository Pattern erfolgen. Jede Entität (z.B. User) erhält ein Interface (z.B. UserRepositoryInterface), das in der Domain-Schicht definiert wird. Die konkrete Implementierung dieses Interfaces (z.B. PdoUserRepository) liegt in der Infrastructure-Schicht.
- Technologie: Wie in Kapitel 3.1.3 festgelegt, ist die alleinige Verwendung der PHP Data Objects (PDO)-Schnittstelle mit Prepared Statements für alle Datenbankinteraktionen verpflichtend.
- Datenzugriffs-API: Die Repository-Interfaces definieren eine klare, domänenspezifische API für den Datenzugriff (z.B. findUserById(int $id), saveUser(User $user)). Sie abstrahieren vollständig von der zugrundeliegenden SQL-Logik.

Begründung:
Diese strikte Trennung von Logik und Datenzugriff durch das Repository Pattern ist ein Kernprinzip der in Kapitel 3.2 definierten Architektur. Sie ermöglicht es, die Datenbanktechnologie auszutauschen, ohne die Anwendungs- oder Domänenlogik zu verändern, und verbessert die Testbarkeit erheblich, da die Repositories in Unit-Tests einfach durch Mock-Objekte ersetzt werden können. Dies erfüllt die Anforderung aus dem Lastenheft (LH: S. 5) nach abstrahierten Datenbankzugriffen und einheitlichen Datenzugriffs-APIs.

### 6.2. Externe Datenschnittstellen (Import/Export)
Grundsatz:
Für den automatisierten Datenaustausch zwischen Nexus und externen Systemen gilt das Prinzip: "Take it as it is, make it how you need it." Das bedeutet, die Verantwortung für die Datentransformation und die Sicherstellung der internen Datenkonformität liegt ausschließlich bei Nexus. Externe Partner müssen ihre Daten nicht in ein Nexus-spezifisches Format umwandeln; Nexus passt sich den Formaten des externen Systems an.

Spezifikation:
- Adapter- und Transformationsschicht: Das Framework stellt ein dediziertes Modul für den Datenimport und -export bereit. Dieses Modul agiert als flexible Transformationsschicht zwischen externen Datenformaten und dem internen Domain-Modell von Nexus.
- Konfigurierbarer Importprozess:
  - Das System muss in der Lage sein, strukturierte Datendateien in verschiedenen Formaten zu verarbeiten (mindestens CSV mit konfigurierbarem Trennzeichen, TSV).
  - Es muss eine administrative Benutzeroberfläche geben, in der Mapping-Konfigurationen für den Datenimport angelegt werden können.
  - Innerhalb dieser Konfiguration muss der Administrator die Spalten der externen Datei den entsprechenden Attributen der internen Nexus-Entitäten (z.B. User, Frachtbrief) zuordnen können. Die Reihenfolge der Spalten in der Quelldatei ist dabei irrelevant.
  - Der Konfigurationsprozess wird durch den Upload einer Beispieldatei unterstützt, um das Mapping der Spalten zu erleichtern.
- Bidirektionale Transformation für den Export:
  - Der Export von Daten aus Nexus in eine Datei muss ebenfalls über die Mapping-Konfigurationen gesteuert werden.
  - Das System nutzt die für den Import definierte Zuordnung, um die Daten aus dem internen Nexus-Format wieder in das exakte Format und die Spaltenreihenfolge des externen Systems zurück zu konvertieren.

Begründung:
Dieser Ansatz senkt die technischen Hürden für die Anbindung von externen Partnern und Systemen drastisch. Er erhöht die Flexibilität und Langlebigkeit des Frameworks, da Anpassungen an externen Datenformaten durch eine einfache Neukonfiguration im Admin-Bereich vorgenommen werden können, ohne dass eine Code-Änderung erforderlich ist. Dies schafft eine robuste und wartbare Lösung für die Integration und den automatisierten Datenaustausch.

### 6.3. Authentifizierungs- und Autorisierungsdienste
Spezifikation:
Das Framework muss die Anbindung an zentrale, externe Authentifizierungsquellen ermöglichen, um Single-Sign-On (SSO) und die Integration in bestehende IT-Infrastrukturen zu unterstützen.
- Provider-Architektur: Das Modul "Userverwaltung und Rechteverwaltung" (PH: 4.2.1) muss eine flexible "Authentication Provider"-Architektur bereitstellen. Es muss möglich sein, über die Konfiguration verschiedene Authentifizierungsmethoden zu aktivieren und zu priorisieren.
- Standard-Provider: Der Standard-Provider ist die lokale Datenbankauthentifizierung (Prüfung von Benutzername/E-Mail und Passwort-Hash gegen die lokale user-Tabelle).
- Externe Provider: Das Framework muss Schnittstellen und erweiterbare Basis-Implementierungen für die Anbindung an folgende externe Dienste bereitstellen:
  - LDAP / Active Directory: Zur Authentifizierung von Benutzern gegen ein zentrales Firmenverzeichnis.
  - OAuth 2.0: Zur Authentifizierung über Drittanbieter wie Google, Microsoft oder GitHub ("Social Login").
  - SAML: Zur Integration in föderierte Identity-Management-Systeme für Enterprise-SSO.
- Just-in-Time Provisioning: Wenn sich ein Benutzer erfolgreich über einen externen Provider authentifiziert, muss das System in der Lage sein, automatisch einen entsprechenden Benutzer-Account in der lokalen Nexus-Datenbank anzulegen ("Just-in-Time Provisioning"), um diesem Benutzer Gruppen und Rollen innerhalb von Nexus zuweisen zu können.

Begründung:
Die Unterstützung von externen Authentifizierungsquellen ist eine explizite Anforderung aus dem Lastenheft (LH: S. 6). Diese Flexibilität ist entscheidend, um die Hürden bei der Einführung von Nexus in bestehenden Unternehmensumgebungen zu senken und den Benutzern einen komfortablen und sicheren Single-Sign-On-Prozess zu ermöglichen.

### 6.4. Programmierschnittstelle für externe Dienste (REST-API)
Spezifikation:
Das Framework muss eine sichere und standardisierte REST-API bereitstellen, um die automatisierte Interaktion mit externen Diensten und Anwendungen zu ermöglichen.
- Architektur: Die API folgt den Prinzipien von REST (Representational State Transfer) und nutzt Standard-HTTP-Methoden (GET, POST, PUT, DELETE) für den Ressourcenzugriff. Die Datenaustauschformate sind JSON.
- Sicherheit: Der Zugriff auf die API muss abgesichert sein. Für die Authentifizierung sind API-Tokens (z.B. Bearer Tokens) vorzusehen, die an Benutzer oder dedizierte "API-Clients" gebunden sind. Die Berechtigungen des API-Zugriffs unterliegen dem gleichen rollenbasierten Rechtesystem (PH: 4.2.1) wie der interaktive Zugriff.
- Versionierung: Die API muss versioniert werden (z.B. /api/v1/...), um zukünftige Änderungen ohne Bruch der Kompatibilität für bestehende Integrationen zu ermöglichen.
- Webhooks: Das System muss einen Mechanismus für ausgehende Webhooks bereitstellen. Wenn in Nexus bestimmte Ereignisse eintreten (z.B. "Neuer Benutzer registriert", "Neuer Forenbeitrag erstellt"), kann das System eine vordefinierte URL eines Drittanbieters per HTTP-POST in Echtzeit benachrichtigen.

Begründung:
Die Bereitstellung einer REST-API und von Webhooks ist eine zentrale Anforderung aus dem Lastenheft (LH: S. 6). Sie ist die Grundlage für die Integration mit externen Systemen (z.B. für Content-Feeds, Google Ads-Automatisierung) und ermöglicht eine flexible Erweiterung des Nexus-Ökosystems durch Drittanwendungen, was die Langlebigkeit und den Nutzen des Frameworks signifikant erhöht.

---