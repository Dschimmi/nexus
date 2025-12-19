# ADR 031: Request Lifecycle Isolation & Internationalization

**Status:** Akzeptiert  
**Datum:** 2025-12-07  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `Kernel`, `SlugService`, `PageManager`

## Kontext

1.  **Sicherheit:** In Long-Running-Runtimes (RoadRunner) teilte der Kernel-Zustand (CSP Nonce) sich über mehrere Requests, was die Sicherheit kompromittierte.
2.  **i18n:** Die URL-Generierung (Slugs) war naiv und zerstörte Umlaute statt sie zu transliterieren (`mller` statt `mueller`).

## Entscheidung

### 1. Stateless Kernel
Der Kernel wurde refactored, um pro Request einen komplett frischen Zustand zu garantieren.
*   **Container:** Wird in `handleRequest` neu gebaut.
*   **Nonce:** Wird in `handleRequest` neu generiert.
*   **Twig Globals:** Werden frisch injiziert.

### 2. Intl-basiertes Slugifying
Einführung eines `SlugService`, der auf `ext-intl` basiert.
*   Nutzung von `transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()')`.
*   Garantiert konsistente, lesbare URLs auch für Unicode-Eingaben.

## Konsequenzen

### Positiv
*   **Sicherheit:** Nonce-Wiederverwendung ist technisch unmöglich.
*   **UX/SEO:** Saubere URLs für internationale Titel.

### Negativ
*   **Performance:** Der Container-Neubau pro Request kostet (minimal) Performance, ist aber für die Sicherheit notwendig (in PHP-FPM ohnehin Standard).