# 2. Spezifikation des Berechtigungskonzepts (RBAC & Security Flow)

## 2.1 Einleitung
Die Sicherheit und Performance des Nexus-Frameworks (und insbesondere des Terminal-Moduls) hängt maßgeblich von einer effizienten Berechtigungsprüfung ab.
**Implementierung:** Permission-Based RBAC mit einer **Hybrid-Gatekeeper-Strategie**.
**Prinzip:** Rollen dienen lediglich als Container für Berechtigungen; der Code prüft ausschließlich auf **atomare Berechtigungen (Permissions)**.

---

## 2.2 Datenstruktur & Relationen (Schema)

### 2.2.1 Kern-Tabellen

| Tabelle          | Felder                                                                 | Beschreibung                                                                 |
|------------------|-----------------------------------------------------------------------|------------------------------------------------------------------------------|
| **permissions**  | `permissionID` (PK), `permissionKey` (Unique String, z.B. `user.create`), `description` | Enthält alle verfügbaren Rechte.                                            |
| **roles**        | `roleID` (PK), `roleName` (Unique, z.B. `ROLE_LAGER`), `roleDescription` | Container für Permissions.                                                   |
| **groups**       | `groupID` (PK), `groupName` (Unique), `groupDescription`               | Organisatorische Einheiten (z.B. "Vertrieb").                              |

---

### 2.2.2 Relationen (Join Tables)

| Tabelle                  | Felder                                                                 | Beschreibung                                                                 |
|--------------------------|-----------------------------------------------------------------------|------------------------------------------------------------------------------|
| **roles_permissions**    | `roleID` (FK), `permissionID` (FK)                                   | Verknüpft Rollen mit Berechtigungen.                                        |
| **users_roles**          | `userID` (FK), `roleID` (FK)                                          | Verknüpft User mit Rollen.                                                   |
| **users_groups**         | `userID` (FK), `groupID` (FK)                                         | Verknüpft User mit Gruppen.                                                  |
| **groups_roles**         | `groupID` (FK), `roleID` (FK)                                         | Verknüpft Gruppen mit Rollen.                                               |

**Logik:**
- Ein User erbt **alle Rollen seiner Gruppen**.
- Berechtigungen sind **kumulativ (additiv)**.

---

## 2.3 Caching-Strategie (Session Snapshot)
**Ziel:** Datenbankentlastung bei jedem Request (Performance-Ziel Terminal).

1. **Login-Event:**
   System lädt alle Rollen des Users + alle Rollen seiner Gruppen.
2. **Flattening:**
   Alle Permissions aus diesen Rollen werden gesammelt, dedupliziert und als **einfaches Array von Strings** (z.B. `['user.create', 'order.view']`) in der **PHP-Session** gespeichert.
3. **Laufzeit:**
   Die Prüfung `$user->can('order.view')` ist ein **reiner Array-Lookup im Arbeitsspeicher**. Keine Datenbankabfrage zur Laufzeit.

---

## 2.4 Hybrid-Gatekeeper (Prüf-Logik)

### 2.4.1 Eingangskontrolle (Gatekeeper)
- **Jeder Controller/Use-Case** definiert eine **Hauptberechtigung** (z.B. `screen.order.edit`).
- **Prüfung:** Vor Instanziierung der Business-Logik.
- **Konsequenz:** Bei fehlender Berechtigung → **Abbruch mit 403 Forbidden**.

---

### 2.4.2 Field-Level-Security (Kontext-Schutz)
**Szenario:** Innerhalb eines genehmigten Screens können einzelne Felder geschützt sein (z.B. Preise sehen, aber nicht ändern).

- **Rendering-Phase:**
  - Server prüft Permissions für kritische Felder.
  - **Beispiel:** Fehlt `order.price.edit` → Feld wird als `protected` markiert.
  - **Konsequenz für Terminal:**
    - Feld existiert im DOM, ist aber **nicht fokussierbar** und aus der Tab-Reihenfolge entfernt.

- **Processing-Phase (Security Audit):**
  - Server empfängt den Input-Buffer.
  - **Prüfung:** War das Feld für diesen User `protected`?
  - **Eskalation bei Verstoß:**
    1. Request wird **abgelehnt (403 Forbidden)**.
    2. **Security Alert** wird geloggt:
       ```plaintext
       "User [ID] sendete Daten für geschütztes Feld [FIELD_NAME]"
       ```
    3. User erhält eine **generische Fehlermeldung** ("Ungültige Datenstruktur").
    4. **Buffer Purge:** Type-Ahead-Puffer des Clients wird sofort geleert.

---

## 2.5 Zusammenfassung für Entwickler
- **❌ Verbotene Praxis:** Niemals auf Rollennamen prüfen (z.B. `if role == 'Admin'`).
- **✅ Richtige Praxis:** Immer auf **Fähigkeiten** prüfen (z.B. `if can('user.delete')`).
- **⚠️ Wichtig:** Vertraue **niemals** darauf, dass ein Feld im UI "grau" ist. Der Server **muss** eingehende Daten gegen die Berechtigung validieren.
