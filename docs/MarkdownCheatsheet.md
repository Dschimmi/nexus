# Markdown Cheatsheet für professionelle PDFs
**Zweck:** Schnellreferenz für Pflichtenhefte und Dokumentation in Projekt Nexus.

---

## 1. Text-Formatierung
Das "Brot und Butter" für lesbare Texte.

| Stil | Syntax | Ergebnis |
| :--- | :--- | :--- |
| **Fett** | `**Fetter Text**` | **Fetter Text** |
| *Kursiv* | `*Kursiver Text*` | *Kursiver Text* |
| ***Fett & Kursiv*** | `***Extrem***` | ***Extrem*** |
| ~~Durchgestrichen~~ | `~~Veraltet~~` | ~~Veraltet~~ |

---

## 2. Struktur & Überschriften
Wichtig: H1 (`#`) bitte nur **einmal** pro Dokument als Haupttitel nutzen.

# Haupttitel (H1)
## Kapitel (H2)
### Unterkapitel (H3)
#### Abschnitt (H4)

---

## 3. Listen (Lists)
Perfekt für Feature-Auflistungen oder Schritt-für-Schritt-Anleitungen.

**Wichtig:** Für Unterpunkte (eingerückt) musst du **zwei Leerzeichen** vor das Minuszeichen oder die Zahl setzen.

**Ungeordnete Liste (Bullet Points):**
- Ein Punkt
- Nächster Punkt
  - Eingerückter Unterpunkt (Hier sind 2 Leerzeichen davor!)
  - Noch ein Detail
- Zurück auf Hauptebene

**Geordnete Liste (Nummerierung):**
1. Schritt Eins
2. Schritt Zwei
3. Schritt Drei
   1. Unterschritt A (Hier sind 3 Leerzeichen davor, damit es unter dem Text beginnt)
   2. Unterschritt B

---

## 4. Code & Technik
Essenziell für technische Dokumentation, um Dateinamen, Befehle oder ganze Funktionen darzustellen.

**Inline Code (im Fließtext):**
Verwende einfache "Backticks" (das Zeichen links neben der Backspace-Taste oder `Shift` + `´`).
Beispiel: Bitte führen Sie `composer install` aus.
Syntax: `` `Befehl` ``

**Code-Blöcke (Mehrzeilig):**
Verwende drei Backticks oben und unten.
**Profi-Tipp:** Schreibe direkt hinter die ersten drei Backticks die Sprache (z.B. `php`, `html`, `css`, `json`, `bash`), damit das PDF den Code farbig hervorhebt (Syntax Highlighting).

```php
// Beispiel: Controller Action
public function index(): Response 
{
    // Hier passiert die Magie
    return new Response('Nexus Core Online');
}

# Beispiel: Terminal-Befehl
'php bin/console cache:clear'

---

## 5. Hinweise & Zitate (Blockquotes)
Ideal, um wichtige Warnungen, Notizen oder Kontext-Infos vom restlichen Text abzuheben.

**Einfacher Hinweis:**
Setze ein `>` Zeichen vor die Zeile.

> **Hinweis:** Dies ist ein wichtiger Hinweis für den Entwickler.
> Er erstreckt sich über mehrere Zeilen.

**Verschachtelte Zitate (für Diskussionen):**
> Erste Ebene
>> Zweite Ebene (Antwort)

---

## 6. Tabellen
Sehr mächtig für Datenmodelle oder Status-Übersichten.

**Syntax:**
Die Spalten werden mit `|` getrennt.
Die zweite Zeile definiert die Ausrichtung:
- `:---` (Links)
- `:---:` (Zentriert)
- `---:` (Rechts)

| ID | Benutzername | Status | Letzter Login |
| :--- | :--- | :---: | ---: |
| 1 | dschimmi | **Aktiv** | 2025-12-05 |
| 2 | admin | Inaktiv | *nie* |

---

## 7. PDF-Spezialfunktionen
Tricks, die speziell für den Export via "Markdown PDF" gedacht sind.

**Seitenumbruch erzwingen (Page Break):**
Füge diese HTML-Zeile ein, damit das nächste Kapitel garantiert auf einer neuen Seite beginnt (z.B. vor jedem neuen Modul-Kapitel).

<div style="page-break-after: always;"></div>

**Bilder einbinden:**
Das Bild muss relativ zur .md Datei liegen.
`![Alternativtext für Blinde](./images/architektur_diagramm.png)`

**Links:**
[Klick mich für Google](https://google.de)