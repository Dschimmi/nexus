# ADR 009: Barrierefreiheit und Compliance-Logik im Frontend

* **Status:** Akzeptiert
* **Datum:** 29.11.2025
* **Kontext:** Meilenstein 0.6.0, Erfüllung PH 7.2 (WCAG) und PH 4.1.4 (DSGVO)

## Kontext und Problemstellung
Web-Elemente wie das Cookie-Banner ("Compliance Modul") müssen nicht nur rechtlich sicher (blockierend), sondern auch barrierefrei bedienbar sein. Nutzer, die nur die Tastatur verwenden, dürfen nicht in einem modalen Dialog "gefangen" sein oder – schlimmer – aus ihm ausbrechen und den unsichtbaren Hintergrund bedienen. Zudem verlangen AdBlocker eine neutrale Benennung von Tracking-relevanten Skripten.

## Entscheidung
Wir implementieren die Interaktionslogik mit **Vanilla JavaScript** und strikten Accessibility-Mustern.

1.  **Focus Trap (Cookie Banner):**
    *   Sobald das Banner aktiv ist, wird der Tastaturfokus per JavaScript im Banner gehalten.
    *   Der Hintergrund wird visuell unkenntlich gemacht (`blur`) und für Maus-Events gesperrt (`pointer-events: none`), um die Blockade zu erzwingen.
    *   Dateibenennung als `compliance.js`, um AdBlocker-Filterlisten zu umgehen.

2.  **Tastaturnavigation:**
    *   Alle interaktiven Elemente (Buttons, Inputs, Tabs) erhalten im CSS `:focus-visible` Styles in der Akzentfarbe Gold (`#f2b400`), um die Position des Fokus klar anzuzeigen (PH 7.2).
    *   Browser-Standard-Outlines werden überschrieben, um Konsistenz zu gewährleisten.

3.  **Responsive Navigation (Subheader):**
    *   Verzicht auf Scrollbalken zugunsten von JS-gesteuerten Scroll-Pfeilen.
    *   Nutzung von `width: max-content` und `margin: 0 auto` im CSS, um Layout-Berechnungen robust gegen JS-Verzögerungen zu machen.

## Konsequenzen
*   **Positiv:** Hohe Barrierefreiheit (WCAG Konformität), robuste Bedienung auf Mobile und Desktop, rechtssichere Umsetzung des Consent-Banners.
*   **Negativ:** Erhöhte Komplexität im JavaScript durch Event-Listener für Fokus-Management.