# 3. Authentifizierung & Provider-Architektur

## 3.1 Grundsatz
Das Nexus-Framework fungiert als **Single Source of Truth** für die Benutzeridentität.
- **Keine modulspezifischen Login-Masken** (z.B. für das Terminal).
- **Single Sign-On (SSO) innerhalb der Anwendung:** Ein Benutzer authentifiziert sich einmal zentral am System und erhält basierend auf seinen Rechten Zugriff auf die verschiedenen Module.

---

## 3.2 Provider-basierte Architektur
**Ziel:** Maximale Flexibilität für unterschiedliche Einsatzszenarien (Standalone, Enterprise-AD, Legacy).

- **UserProviderInterface:**
  Das Framework definiert eine Schnittstelle, die vorschreibt, wie Benutzerdaten geladen werden (z.B. `loadUserByUsername()`).

- **Implementierungen:**

  | Provider                | Beschreibung                                                                                     |
  |--------------------------|-------------------------------------------------------------------------------------------------|
  | **FileUserProvider**     | Legacy/Fallback: Liest den Admin-User aus Konfigurationsdateien (`.env`). Dient als Notfall-Zugang. |
  | **DatabaseUserProvider** | Standard: Authentifiziert gegen die lokale `users`-Datenbanktabelle.                          |
  | **LdapUserProvider**     | Erweiterung: Ermöglicht Authentifizierung gegen Windows/Active Directory.                     |
  | **SsoProvider**          | Erweiterung: Unterstützung für OAuth/Externe Identitätsprovider.                              |

---

## 3.3 Integration des Terminal-Moduls
- **Zugriffsteuerung:**
  Der Einstieg in den **"Work Mode"** erfordert **keine erneute Passworteingabe**.
  Stattdessen prüft der **Gatekeeper**, ob der aktuell eingeloggte User die Berechtigung `terminal.access` besitzt.

- **Re-Authentifizierung (Optional):**
  Für **kritische Aktionen** (z.B. "Inventurabschluss") muss das Framework einen Mechanismus bereitstellen, um das Passwort **kurzzeitig erneut abzufragen** ("Sudo-Mode"), **ohne die Session zu beenden**.

---

## 3.4 Credentials & Sicherheit (Hashing und 2FA)

### 3.4.1 Passwort-Hashing (Argon2id)
- **Algorithmus:** **Argon2id** (resistent gegen GPU-Brute-Force-Angriffe und Seitenkanalattacken).
- **Konfiguration:**
  - Kostenfaktoren (Memory Limit, Time Cost, Threads) **müssen im Backend konfigurierbar** sein, um eine Balance zwischen Sicherheit und Server-Last zu finden.
  - **Default:** Orientierung an den aktuellen **OWASP-Empfehlungen** (z.B. 64MB RAM).
- **Re-Hashing:**
  Beim Login prüft das System (`password_needs_rehash`), ob der gespeicherte Hash den aktuellen Sicherheitseinstellungen entspricht.
  Falls nicht, wird das Passwort mit den neuen Parametern **neu gehasht und gespeichert** (automatische Migration).

---

### 3.4.2 Zwei-Faktor-Authentifizierung (2FA) – Vorbereitung
**Status:** Optional im ersten Release, aber die **Datenstruktur muss vorbereitet** sein ("Database First").

- **Datenmodell:**
  Die `users`-Tabelle (oder eine **1:1-Relation `user_secrets`**) enthält ein Feld `twoFactorSecret` (verschlüsselt gespeichert), um das **TOTP-Secret** (Time-based One-Time Password) für Apps wie Google Authenticator aufzunehmen.

- **Flow-Support:**
  Die Authentifizierungslogik muss darauf ausgelegt sein, **nach der Passwortprüfung einen zweiten Schritt** einzuschieben, bevor der Benutzer als "eingeloggt" gilt.
