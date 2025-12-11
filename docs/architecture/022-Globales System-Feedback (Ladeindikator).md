# ADR 022: Globales System-Feedback (Ladeindikator)

**Status:** Akzeptiert
**Datum:** 2025-12-11
**Autor:** Architecture Team
**Betroffene Komponenten:** `base.html.twig`, `components.css`, `layout.js`

## Kontext
Benutzer benötigen bei Server-Anfragen visuelles Feedback, um zu erkennen, dass das System arbeitet (Ticket 0000047). Eine Blockierung der Oberfläche (Modal) ist jedoch explizit nicht erwünscht, um den Arbeitsfluss nicht zu unterbrechen.

## Entscheidung
Wir implementieren einen **nicht-blockierenden Ladeindikator**.

### 1. Visuelles Overlay (Pass-Through)
Der Spinner wird zentral über dem Inhalt platziert (`position: fixed`), das Container-Overlay ist jedoch für Maus-Events transparent (`pointer-events: none`).
* **Verhalten:** Der Benutzer sieht den Spinner, kann aber weiterhin Links klicken oder scrollen.
* **Hintergrund:** Kein Abdunkeln des Contents (Background transparent).

### 2. Steuerung
Die Steuerung erfolgt global über JavaScript (`layout.js`), welches sich in den `submit`-Prozess von Formularen einklinkt.
* **Status-Klasse:** Wir nutzen `.active` (passend zum Projektstandard), um den Spinner einzublenden.

## Konsequenzen

### Positiv
* **UX:** Störungsfreies Arbeiten. Keine "eingefrorene" Oberfläche.
* **Konsistenz:** Fügt sich nahtlos in die bestehende UI-Logik ein.

### Negativ
* **Race Conditions:** Da der User weiterklicken kann, besteht das Risiko von "Double Submits". Dies wird als akzeptiertes Risiko betrachtet und muss bei Bedarf auf Button-Ebene gelöst werden.