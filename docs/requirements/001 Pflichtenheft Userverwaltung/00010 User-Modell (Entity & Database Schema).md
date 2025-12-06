# 1. User-Modell (Entity & Database Schema)

## 1.1 Tabellenname & Präfix
- **Tabellenname:** `nex_users` (oder systemkonform mit Präfix)
- **Zweck:** Zentrale Speicherung aller Benutzerdaten.

---

## 1.2 Identifikation & Authentifizierung

| Feldname (Property)         | Datentyp (PHP)         | Datentyp (SQL)                     | Verwendung & Hinweise                                                                 |
|-----------------------------|------------------------|------------------------------------|--------------------------------------------------------------------------------------|
| userID                      | int                    | BIGINT UNSIGNED AUTO_INCREMENT PK | Eindeutige ID. BigInt für Zukunftssicherheit.                                        |
| userName                    | string                 | VARCHAR(190) UNIQUE               | Login-Name. Muss unique sein. 190 Zeichen wegen utf8mb4-Index-Limitierung in MySQL.   |
| userMail                    | string                 | VARCHAR(255) UNIQUE               | E-Mail-Adresse (Login-Alternative & Kommunikation). Validierung formatseitig zwingend. |
| userPassword                | string                 | VARCHAR(255)                      | Enthält den Argon2id Hash. Niemals Klartext.                                         |

---

## 1.3 Status & Lifecycle (State Machine)

| Feldname (Property)         | Datentyp (PHP)         | Datentyp (SQL)                     | Verwendung & Hinweise                                                                 |
|-----------------------------|------------------------|------------------------------------|--------------------------------------------------------------------------------------|
| userState                   | string (Enum)          | VARCHAR(20)                        | Statuswerte: active, locked, pending, archived. Steuert globalen Zugriff.             |
| userCreatedAt               | DateTimeImmutable      | DATETIME                           | Erstellungszeitpunkt. Unveränderlich.                                                 |
| userUpdatedAt               | DateTime               | DATETIME                           | Letzte Änderung am Profil (automatisch aktualisiert).                                 |
| userArchivedAt              | ?DateTime              | DATETIME NULL                      | Wenn gesetzt: User ist archiviert (ReadOnly), aber referenziell integer.              |
| userDeletedAt               | ?DateTime              | DATETIME NULL                      | Für Soft-Deletes. Logisch nicht mehr existent (Papierkorb).                            |

---

## 1.4 Login-Metriken & Sicherheit

| Feldname (Property)         | Datentyp (PHP)         | Datentyp (SQL)                     | Verwendung & Hinweise                                                                 |
|-----------------------------|------------------------|------------------------------------|--------------------------------------------------------------------------------------|
| userLastLoginAt             | ?DateTime              | DATETIME NULL                      | Zeitstempel des letzten erfolgreichen Logins.                                          |
| userLastLoginIp             | ?string                | VARCHAR(45) NULL                   | IP (IPv4/IPv6). DSGVO: Muss ggf. nach X Tagen anonymisiert werden.                   |
| userLoginCount              | int                    | INT UNSIGNED DEFAULT 0             | Zähler erfolgreicher Logins (Statistik).                                              |
| userFailedLoginCount        | int                    | INT UNSIGNED DEFAULT 0             | Zähler fehlgeschlagener Versuche seit letztem Erfolg. Trigger für Sperre.            |
| userLastFailedLoginAt       | ?DateTime              | DATETIME NULL                      | Zeitstempel des letzten Fehlversuchs (Brute-Force-Drosselung).                        |
| userIsEmailVerified         | bool                   | TINYINT(1) DEFAULT 0               | true, wenn Double-Opt-In abgeschlossen.                                               |
| userEmailVerifiedAt         | ?DateTime              | DATETIME NULL                      | Wann wurde die Mail verifiziert?                                                       |
| userPasswordChangeAt        | ?DateTime              | DATETIME NULL                      | Wann wurde das Passwort zuletzt geändert? (Erzwingen von Rotation möglich).           |

---

## 1.5 Einstellungen & Profildaten

| Feldname (Property)         | Datentyp (PHP)         | Datentyp (SQL)                     | Verwendung & Hinweise                                                                 |
|-----------------------------|------------------------|------------------------------------|--------------------------------------------------------------------------------------|
| userLanguage                | string                 | VARCHAR(5) DEFAULT 'de'            | ISO-Code (z.B. de, en-US) für i18n.                                                   |
| userTimezone                | string                 | VARCHAR(50) DEFAULT 'Europe/Berlin'| Wichtig für korrekte Datumsanzeigen über Zeitzonen hinweg.                            |
| userPreferences             | array                  | JSON                                | Key-Value-Store für Moduleinstellungen (z.B. Terminal-Settings).                     |
| userRealFirstName           | ?string                | VARCHAR(100) NULL                  | Optional: Vorname (für E-Mails/Unterschriften).                                        |
| userRealLastName            | ?string                | VARCHAR(100) NULL                  | Optional: Nachname.                                                                    |

---