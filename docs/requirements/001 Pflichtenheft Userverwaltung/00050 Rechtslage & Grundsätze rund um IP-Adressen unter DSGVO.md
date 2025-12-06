# 5. Rechtslage & Grundsätze rund um IP-Adressen unter DSGVO

## 5.1 Einordnung der IP-Adresse als personenbezogenes Datum
- Eine **IP-Adresse** zählt als **personenbezogenes Datum**, da sie — zumindest in Kombination mit weiteren Daten oder mit Hilfe des Internetdienstanbieters — eine Zuordnung zu einer natürlichen Person ermöglichen kann.
- **Rechtsgrundlage:** Art. 6 Abs. 1 lit. f DSGVO („berechtigtes Interesse“).
  - Beispiel: Sicherheit, Betrieb der Website, Missbrauchsabwehr.

---

## 5.2 Transparenzpflichten für Nutzer
Wenn **vollständige IP-Adressen gespeichert** werden, müssen folgende Punkte in der **Datenschutzerklärung** oder einem **Hinweis** enthalten sein:

| Punkt                     | Beschreibung                                                                                     |
|---------------------------|-------------------------------------------------------------------------------------------------|
| **Erhebung & Speicherung** | Klare Information, dass IP-Adressen gespeichert werden und es sich um personenbezogene Daten handelt. |
| **Zweck**                 | Beispiel: Logfiles, Sicherheit, Missbrauchsschutz, ggf. Nachweispflicht (z.B. bei unbefugtem Zugriff). |
| **Rechtsgrundlage**       | Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse).                                           |
| **Speicherdauer**         | Wie lange werden die IPs gespeichert? Wann werden sie gelöscht oder anonymisiert?               |
| **Betroffenenrechte**     | Auskunft, Löschung, ggf. Widerspruch — je nach Verarbeitungsszenario.                          |
| **Cookies & Tracking**     | Falls neben IP auch Cookies gesetzt werden: **Einwilligung** über Cookie-Banner nötig (wenn nicht „technisch notwendig“). |

**Hinweis:**
Ein reines Cookie-Banner reicht **nicht** für die Speicherung der IP allein — IP-Logging per se setzt meist kein Cookie, aber es sind trotzdem personenbezogene Daten.

---

## 5.3 Speicherdauer: Best Practices
- **Keine pauschale maximale Dauer** in der DSGVO vorgeschrieben.
- **Gängige Praxis:**
  - **Kurzfristig (z.B. 7 Tage):** Für Logfiles zur Funktions- und Sicherheitssicherung.
  - **Längerfristig:** Nur bei Nachweispflicht oder Sicherheitsvorfällen, dann mit **Anonymisierung/Pseudonymisierung** verbinden.
  - **Unendliche Speicherung:** Nur zulässig bei **überzeugenden Gründen** (legitimes Interesse) und **transparenter Information** der Nutzer.

---

## 5.4 Empfehlungen für DSGVO-konformes IP-Logging

### 5.4.1 Transparenz
- In der **Datenschutzerklärung** klar und verständlich aufzeigen:
  - Dass IP-Adressen gespeichert werden.
  - Zu welchem **Zweck** (z.B. Sicherheit, Missbrauchsschutz).
  - Auf welcher **Rechtsgrundlage** (z.B. Art. 6 Abs. 1 lit. f DSGVO).
  - Wie lange die Daten gespeichert werden.

### 5.4.2 Datenminimierung
- **Löschfristen definieren** (z.B. 7–30 Tage).
- Bei längerer Speicherung: **Anonymisierung oder Löschung**, sobald der Zweck entfällt.

### 5.4.3 Nutzerrechte
- **Auskunft, Löschung, Widerspruch** müssen gewährleistet sein.
- **Keine Einwilligung nötig** für reines IP-Logging (außer bei zusätzlichem Tracking/Cookies).

### 5.4.4 Technische Umsetzung
- **Cloudflare Turnstile** oder ähnliche Lösungen:
  - Wird als **„notwendig“** eingestuft (Schutz der Infrastruktur).
  - **Kein Cookie-Banner** nötig, wenn **nur IP-Logging** (ohne Tracking-Cookies).
  - **Zwang:** Bei Aktivierung muss das Token für Login/Registrierung vorliegen.
  - **Fehlerbehandlung:** Bei fehlendem Token (z.B. durch AdBlocker) → Hinweis an Nutzer: *„Bitte Sicherheitsmodul zulassen“*.
