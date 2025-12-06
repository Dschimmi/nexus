# 6. Schnittstellen und Integrationen

---

## 6.1 Fallback-Logik für die Userverwaltung
- **AuthenticationService** prüft beim Boot:
  *„Ist das User-Modul installiert und aktiv?“*
  - **JA:** Nutze `DatabaseUserProvider` (greift auf die `users`-Tabelle zu).
  - **NEIN:** Nutze `FileUserProvider` (greift auf `.env`/Config-Datei zu).

---

## 6.2 Migration: Import des Legacy-Admins
- **CLI-Befehl:** `nexus:user:import-env-admin`
  - Liest `ADMIN_USER` und `ADMIN_PASSWORD_HASH` aus der `.env`.
  - Erstellt einen User in der Datenbank mit diesen Daten.
  - Weist diesem User automatisch die Rolle `ROLE_ADMIN` (oder äquivalente Super-Admin-Rechte) zu.
  - **Zweck:** Verhindert, dass sich Administratoren nach Aktivierung des Moduls aussperren („Lockout Protection“).

---

## 6.3 Provider-Swap: Ablösung des FileUserProviders
- **Schritte:**
  1. Das Modul registriert seinen `DatabaseUserProvider` im **DI-Container**.
  2. In der `config/services.php` des Hauptprojekts wird der bisherige `FileUserProvider` durch die **Datenbank-Implementierung ersetzt**.
- **Resultat:**
  Das gesamte Framework (Login, Admin-Panel) authentifiziert ab sofort gegen die Datenbank.
  Die `.env`-Credentials werden ignoriert (außer für den Notfall-Import).

---

## 6.4 CLI-Befehle für Setup und Wartung
   Befehl                     | Beschreibung                                                                                     |
 |----------------------------|-------------------------------------------------------------------------------------------------|
 | `nexus:user:install`       | Erstellt die **Tabellenstruktur** (`users`, `roles`, `permissions`, `groups` etc.).             |
 | `nexus:user:create-admin`  | **Interaktiver Wizard** zum Anlegen des ersten Administrators.                                  |
 | `nexus:user:import-env-admin` | Importiert den Legacy-Admin aus der `.env` in die Datenbank (siehe 6.2).                       |

---

## 6.5 Authentifizierungs-Brücke: Core und Modul
- **Core-Interface:** `Nexus\Core\Contract\UserProviderInterface`
- **Modul-Implementierung:** `Nexus\Module\UserManagement\Infrastructure\DatabaseUserProvider`
- **Dependency Injection:**
  - Der Core kennt das Modul **nicht direkt**, nutzt es aber über das Interface.
  - In der Config wird dem Core-Service die Implementierung aus dem Modul **injiziert** (Inversion of Control).

---

## 6.6 Integration externer Authentifizierungsquellen

### 6.6.1 LDAP/Active Directory
- **Provider:** `LdapUserProvider`
- **Konfiguration:**
  - Host, Port, Base DN, Bind DN/Password in der `config/user_management.php`.
  - **Fallback:** Bei Ausfall des LDAP-Servers → Wechsel auf `DatabaseUserProvider`.

### 6.6.2 Single Sign-On (SSO)
- **Provider:** `SsoProvider`
- **Unterstützte Protokolle:** OAuth2, SAML (über Erweiterungen).
- **Flow:**
  1. Nutzer wird zum externen Identity Provider (IdP) weitergeleitet.
  2. Nach erfolgreicher Authentifizierung → Rückgabe eines **JWT** oder **SAML-Assertion**.
  3. Modul validiert das Token und erstellt eine **lokale Session**.

### 6.6.3 Social Login (OAuth)
- **Tabelle:** `user_social_identities` (siehe 4.4.2.3)
- **Unterstützte Anbieter:** Google, Facebook, Microsoft (erweiterbar).
- **Flow:**
  1. Nutzer wählt Anbieter (z.B. „Mit Google anmelden“).
  2. OAuth-Flow → Rückgabe eines **Access Tokens**.
  3. Modul prüft/erstellt den Nutzer in der `users`-Tabelle und verknüpft die soziale Identität.

---

## 6.7 API-Schnittstellen für Drittanbieter

### 6.7.1 REST-API für Userverwaltung
- **Endpunkte:**
  - `GET /api/users` → Liste aller User (mit Pagination).
  - `POST /api/users` → User anlegen.
  - `GET /api/users/{id}` → User-Details.
  - `PATCH /api/users/{id}` → User aktualisieren.
  - `DELETE /api/users/{id}` → User löschen (Soft Delete).
- **Authentifizierung:** API-Key oder OAuth2-Token (konfigurierbar).
- **Berechtigungen:** Jeder Endpunkt erfordert spezifische Permissions (z.B. `user.list`, `user.create`).

### 6.7.2 Webhooks für Ereignisse
- **Unterstützte Events:**
  - `user.created`
  - `user.updated`
  - `user.deleted`
  - `user.login`
  - `user.failed_login`
- **Konfiguration:**
  - Webhook-URLs in der `config/user_management.php`.
  - **Signatur:** Jeder Webhook-Call wird mit einem **HMAC-Signatur-Header** versehen.

---

## 6.8 Integration mit dem Terminal-Modul
- **Vertrauensmodell:**
  Das Terminal-Modul vertraut **vollständig** der bestehenden Nexus-Session.
  - Keine erneute Passworteingabe für den „Work Mode“.
  - Prüfung der Berechtigung `terminal.access` durch den Gatekeeper.
- **Echtzeit-Synchronisation:**
  - Änderungen an User-Rechten werden **sofort** in der Session aktualisiert (via Event-Listener).
  - Bei kritischen Aktionen (z.B. „Inventurabschluss“) → **Re-Authentifizierung** („Sudo-Mode“).