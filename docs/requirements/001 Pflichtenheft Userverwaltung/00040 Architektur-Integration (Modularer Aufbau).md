# 4. Architektur-Integration (Modularer Aufbau)

## 4.1 Einleitung
**Ziel:** Maximale Wartbarkeit und ein schlanker Core.
**Umsetzung:** Die Userverwaltung wird als eigenständiges Modul im Verzeichnis `/modules/UserManagement/` implementiert (**Modularer Monolith**).

---

## 4.2 Physische Struktur & Autoloading
Das Modul kapselt **alle Bestandteile** (Logik, Templates, Assets).

- **Namespace:** `Nexus\Module\UserManagement\`
- **Composer:**
  Der Pfad `modules/` wird in der `composer.json` als **PSR-4 Autoload-Pfad** registriert.
- **Schichtentrennung:**
  Innerhalb des Modul-Ordners wird die **Hexagonale Architektur** (Domain, Application, Infrastructure, Presentation) **strikt eingehalten**.

---

## 4.3 Registrierung im Core (Wiring)
Damit Nexus das Modul kennt, muss es geladen werden:

- **Service-Loader:**
  Die zentrale `config/services.php` des Frameworks importiert die `modules/UserManagement/config/services.php`.
  **Ergebnis:** Die Modul-Services (z.B. `UserManager`) landen im **globalen DI-Container**.

- **Router:**
  Die zentrale `config/routes.php` importiert die **Routen-Definitionen** des Moduls.

- **Template-Engine:**
  Der Pfad `modules/UserManagement/templates/` wird in **Twig** als Namespace `@UserManagement` registriert.

---

## 4.4 Userverwaltung und Rechteverwaltung

### 4.4.1 Architektur & Integration (Modularer Monolith)
- **Speicherort:** `/modules/UserManagement/`
- **Struktur:** Innerhalb des Moduls wird die **Hexagonale Architektur** (Domain, Application, Infrastructure, Presentation) strikt eingehalten.
- **Integration:**
  Die Registrierung im Framework erfolgt über **Config-Imports** (`services.php`, `routes.php`) und **Dependency Injection**.
  Das Modul stellt eine **Implementierung des `UserProviderInterface`** bereit, die bei Aktivierung den **Standard-File-Provider ersetzt**.

---

### 4.4.2 Das Datenmodell (Entities)

#### 4.4.2.1 Die User-Entität (Tabelle `users`)

| Kategorie               | Felder                                                                                     | Beschreibung                                                                                     |
|-------------------------|--------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------|
| **Identifikation**      | `userID` (BigInt, PK), `userName` (Unique), `userMail` (Unique)                              | Eindeutige Identifikation des Benutzers.                                                         |
| **Authentifizierung**   | `userPassword` (Argon2id Hash), `userTwoFactorSecret` (Vorbereitung für 2FA)                 | Sichere Speicherung der Zugangsdaten.                                                           |
| **Status & Lifecycle**  | `userState` (Enum: `active`, `locked`, `pending`, `archived`), `createdAt`, `updatedAt`, `deletedAt` (Soft Delete) | Verwaltung des Benutzerlebenszyklus.                                                           |
| **Sicherheit & Audit**  | `lastLoginAt`, `lastLoginIp` (Anonymisiert gem. DSGVO), `failedLoginCount`, `passwordChangeAt` | Protokollierung von Login-Aktivitäten und Sicherheitsmetriken.                                  |
| **Einstellungen**       | `userPreferences` (JSON-Feld für Modul-Configs wie "Terminal Sound")                        | Benutzerindividuelle Konfigurationen.                                                          |

---

#### 4.4.2.2 RBAC-Entitäten (Tabellen & Relationen)

| Tabelle                  | Beschreibung                                                                                     |
|--------------------------|-------------------------------------------------------------------------------------------------|
| **permissions**          | Die kleinste Einheit (z.B. `user.create`, `terminal.access`).                                   |
| **roles**                | Container für Permissions (z.B. `ROLE_ADMIN`).                                                  |
| **groups**               | Organisatorische Einheiten (z.B. "Vertrieb").                                                   |
| **users_groups**         | Verknüpft User mit Gruppen (1:n).                                                                |
| **users_roles**          | Verknüpft User mit Rollen (1:n).                                                                |
| **groups_roles**         | Verknüpft Gruppen mit Rollen (1:n).                                                             |
| **roles_permissions**    | Verknüpft Rollen mit Berechtigungen (1:n).                                                      |

**Logik:**
Die Rechte eines Users sind die **Summe aller Permissions** seiner direkten Rollen **PLUS** der Rollen seiner Gruppen (Kumulation).

---

#### 4.4.2.3 Social Login (Tabelle `user_social_identities`)

| Feldname               | Beschreibung                                                                                     |
|------------------------|-------------------------------------------------------------------------------------------------|
| `identityID` (PK)      | Eindeutige ID der sozialen Identität.                                                            |
| `userID` (FK)          | Verknüpfung zur `users`-Tabelle.                                                                |
| `provider`             | Name des Providers (z.B. `google`, `github`).                                                   |
| `providerUid`          | Eindeutige ID des Users beim Anbieter.                                                          |
| `accessToken`          | Optional/Encrypted, falls API-Zugriffe im Namen des Users nötig sind.                           |

**Logik:**
Ein Nexus-User kann **mehrere soziale Identitäten** besitzen (z.B. Login via Google **UND** GitHub möglich).

---

### 4.4.3 Sicherheitskonzept & Authentifizierung

#### 4.4.3.1 Authentifizierung (Login)
- **Provider-Pattern:** Das Modul nutzt den `DatabaseUserProvider`.
- **Erweiterbarkeit:** LDAP/SSO ist durch die Architektur vorbereitet.
- **Credentials:**
  - Passwörter werden **ausschließlich mit Argon2id gehasht**.
  - **Brute-Force-Schutz:** Nach N fehlgeschlagenen Versuchen (`failedLoginCount`) wird der Account temporär oder dauerhaft gesperrt (`userState = locked`).
- **Session-Sicherheit:**
  Bei jeder Änderung des Sicherheitskontexts (Login, Wechsel der Benutzerrolle, Rechteanpassung) **muss die Session-ID zwingend neu generiert werden** (`regenerateId()`), um **Session Fixation** zu verhindern.

---

#### 4.4.3.2 Autorisierung (RBAC & Gatekeeper)
**Ziel:** Performance (für das Terminal) und Sicherheit vereinen.

- **Session Snapshot (Caching):**
  - Beim Login werden **alle Permissions**, die explizit dem authentifizierten Benutzer (direkt oder über Gruppen) zugeordnet sind, aus der Datenbank geladen.
  - Diese Liste wird als **flaches Array** in den `security-Bag` der Session geschrieben.
  - Laufzeit-Prüfungen (`can()`) erfolgen **ausschließlich gegen den RAM**, nicht gegen die Datenbank.

- **Der Gatekeeper (Eingangskontrolle):**
  - Jeder Controller/Use-Case definiert eine **Hauptberechtigung** (z.B. `user.manage`).
  - Diese wird **vor Ausführung der Logik geprüft**.

- **Field-Level-Security (Intrusion Detection):**
  - Der Server prüft bei **schreibenden Zugriffen**, ob der User die Berechtigung für spezifische Felder hat (z.B. "Preise ändern").
  - **Eskalation bei Verstoß:**
    1. Request wird **hart abgelehnt (403 Forbidden)**.
    2. **Security Alert** wird im System-Log erstellt:
       ```plaintext
       "Security Alert: User [ID] attempted to write protected field [FIELD_NAME]"
       ```
    3. User erhält eine **generische Fehlermeldung** ("Verarbeitungsfehler: Ungültige Datenstruktur").
    4. **Buffer Purge:** Der Type-Ahead-Puffer des Clients wird sofort geleert.

---

#### 4.4.3.3 Spam-Prävention & Bot-Schutz
**Ziel:** Öffentliche Formulare (Registrierung, Login) müssen effektiv gegen Missbrauch geschützt werden.

| Mechanismus               | Beschreibung                                                                                     |
|----------------------------|-------------------------------------------------------------------------------------------------|
| **Honeypot**               | Jedes Formular enthält **unsichtbare Felder**. Füllt ein Bot diese aus, wird die Anfrage stillschweigend verworfen. |
| **Rate Limiting**          | IP-basierte Drosselung (z.B. max. 5 Versuche pro Minute). Bei Überschreitung: **429 Too Many Requests**. |
| **Cloudflare Turnstile**  | Native Integration für **Smart Challenges**.                                                   |
| **Datenschutz-Einordnung**| Turnstile wird technisch als **"Notwendig" (Essential)** eingestuft (Schutz der Infrastruktur). |
| **Zwang**                 | Ist Turnstile aktiviert, ist das Token für Login/Registrierung **zwingend erforderlich**.       |
| **Error-Handling**        | Fehlt das Token (z.B. durch NoScript/AdBlocker): Request wird abgelehnt, User erhält eine Warnung ("Bitte Sicherheitsmodul zulassen"). |
