/**
 * DATEI: layout.js
 * ZWECK: Steuert NUR die Sichtbarkeit der Pfeile.
 * Die Ausrichtung (Zentriert vs Links) macht jetzt das CSS (margin: 0 auto).
 */

document.addEventListener('DOMContentLoaded', initSubheaderScroll);
window.addEventListener('load', initSubheaderScroll);

function initSubheaderScroll() {
    const wrapper = document.getElementById('nav-scroll-wrapper');
    const leftBtn = document.getElementById('nav-scroll-left');
    const rightBtn = document.getElementById('nav-scroll-right');
    const SCROLL_AMOUNT = 200;

    if (!wrapper || !leftBtn || !rightBtn) return;

    const checkArrows = () => {
        // Toleranz für Rundungsfehler (Zoom-Stufen)
        const tolerance = 2;
        
        // Werte runden für präzisen Vergleich
        const scrollWidth = Math.ceil(wrapper.scrollWidth);
        const clientWidth = Math.ceil(wrapper.clientWidth);
        const scrollLeft = Math.ceil(wrapper.scrollLeft);

        // Ist der Inhalt breiter als der sichtbare Bereich?
        const hasOverflow = scrollWidth > clientWidth;

        if (!hasOverflow) {
            // Kein Überlauf -> Beide Pfeile weg
            leftBtn.style.display = 'none';
            rightBtn.style.display = 'none';
        } else {
            // Überlauf -> Pfeile basierend auf Position zeigen
            
            // Linker Pfeil: Zeigen, wenn wir nach rechts gescrollt haben
            leftBtn.style.display = scrollLeft > tolerance ? 'flex' : 'none';
            
            // Rechter Pfeil: Zeigen, wenn noch Platz rechts ist
            // (scrollLeft + clientWidth) ist die rechte Kante des sichtbaren Bereichs
            const remainingSpace = scrollWidth - (scrollLeft + clientWidth);
            rightBtn.style.display = remainingSpace > tolerance ? 'flex' : 'none';
        }
    };

    // Button Events
    leftBtn.onclick = () => {
        wrapper.scrollBy({ left: -SCROLL_AMOUNT, behavior: 'smooth' });
    };

    rightBtn.onclick = () => {
        wrapper.scrollBy({ left: SCROLL_AMOUNT, behavior: 'smooth' });
    };

    // Listener
    wrapper.addEventListener('scroll', checkArrows);
    window.addEventListener('resize', checkArrows);

    // Initialer Check
    checkArrows();
}

/* =========================================
   GLOBAL SHORTCUTS
   ========================================= */

document.addEventListener('keydown', function(event) {
    // Strg + K (oder Cmd + K auf Mac) abfangen
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        const searchInput = document.querySelector('.header-search');
        
        // Nur wenn das Suchfeld tatsächlich existiert (Modul aktiviert)
        if (searchInput) {
            event.preventDefault(); // Browser-Verhalten unterdrücken
            searchInput.focus();    // Fokus in unser Feld setzen
        }
    }
});