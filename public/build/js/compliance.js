/**
 * DATEI: compliance.js
 * ZWECK: Steuerung des DSGVO-Cookie-Banners.
 * FUNKTIONEN:
 *  - Anzeigen/Verstecken des Banners (Modal-Logik).
 *  - Barrierefreiheit: Focus-Trap (Fokus im Banner fangen) und Hintergrund-Blur.
 *  - Kommunikation: Senden der Entscheidung (Accept/Decline) an den Server.
 */

// Speichert das letzte fokussierte Element innerhalb des Banners.
// Dient dazu, den Fokus zurückzusetzen, falls der Nutzer versucht, das Banner zu verlassen.
let lastFocusedElement = null;

/**
 * Initialisierung beim Laden der Seite.
 * Prüft, ob ein Banner vorhanden ist und ob bereits eine Entscheidung vorliegt.
 */
document.addEventListener('DOMContentLoaded', () => {
    const banner = document.getElementById('cookie-banner');
    const consent = localStorage.getItem('cookie_consent');
    
    // Logik: Falls das Banner im DOM existiert, aber noch keine Entscheidung im LocalStorage liegt.
    // Aktuell wird das Banner standardmäßig versteckt (display: none) gerendert.
    // Eine automatische Öffnung könnte hier implementiert werden:
    if (banner && !consent) {
        showBanner(); // Zwingend: Automatisch öffnen (DSGVO/TTDSG)
    }
});

/**
 * Globale Funktion zum Anzeigen des Banners.
 * Wird typischerweise über den Link "Cookie-Einstellungen" im Footer aufgerufen.
 *
 * @param {Event} event - Das Klick-Event (optional), um das Standardverhalten (Springen) zu verhindern.
 */
window.showCookieBanner = function(event) {
    if (event) event.preventDefault();
    showBanner();
};

/**
 * Verarbeitet die Entscheidung des Nutzers (Klick auf Button).
 * Implementiert das "Optimistic UI" Pattern (sofortiges Schließen vor Server-Antwort).
 *
 * @param {boolean} accepted - True für "Alle akzeptieren", False für "Nur Essentielle".
 */
window.handleCookie = function(accepted) {
    // 1. Entscheidung clientseitig speichern (für schnelle lokale Prüfung)
    const value = accepted ? 'all' : 'essential';
    localStorage.setItem('cookie_consent', value);
    
    // 2. UI sofort schließen (verbessert die wahrgenommene Geschwindigkeit)
    hideBanner();
    console.log('Compliance: Cookies gesetzt auf:', value);

    // 3. Entscheidung asynchron an den Server senden
    const endpoint = accepted ? '/consent/accept' : '/consent/decline';

    fetch(endpoint, {
        method: 'POST',
        headers: { 
            'X-Requested-With': 'XMLHttpRequest', // Kennzeichnung als AJAX-Request
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (response.ok) {
            console.log('Compliance: Server-Synchronisation erfolgreich.');
        } else {
            console.warn('Compliance: Server antwortete mit Status', response.status);
        }
    })
    .catch(error => console.error('Compliance: Fehler bei Server-Synchronisation:', error));
};

// =============================================================================
// INTERNE HELPER-FUNKTIONEN
// =============================================================================

/**
 * Öffnet das Banner, setzt CSS-Klassen und aktiviert die Barrierefreiheits-Features.
 */
function showBanner() {
    const banner = document.getElementById('cookie-banner');
    const wrapper = document.getElementById('app-wrapper');
    
    if (banner) {
        // CSS-Klassen setzen (.show für Flexbox, .blur-content für den Hintergrund)
        banner.classList.add('show');
        
        if (wrapper) {
            wrapper.classList.add('blur-content');
        }
        
        // 1. Initialen Fokus setzen (Barrierefreiheit)
        // Wir fokussieren den "Alle akzeptieren" Button (Primary), da dies die gewünschte Handlung ist.
        const primaryBtn = banner.querySelector('.btn--primary');
        
        if (primaryBtn) {
            // HACK: Kleiner Timeout (50ms) ist notwendig, da der Browser das Element
            // erst rendern muss (display: none -> flex), bevor er den Fokus akzeptiert.
            setTimeout(() => {
                primaryBtn.focus();
                lastFocusedElement = primaryBtn; // Startpunkt für Focus-Trap merken
            }, 50);
        }
        
        // 2. Event-Listener für Focus-Trap aktivieren
        banner.addEventListener('keydown', handleTabKey); // Tab-Taste abfangen
        banner.addEventListener('focusin', trackFocus);   // Fokus-Bewegung verfolgen
        
        // 3. Ausbrechen des Fokus verhindern (Klicks/Fokus außerhalb)
        document.addEventListener('focusin', enforceFocus);
        document.addEventListener('click', enforceFocus);
    }
}

/**
 * Schließt das Banner und räumt Event-Listener auf.
 */
function hideBanner() {
    const banner = document.getElementById('cookie-banner');
    const wrapper = document.getElementById('app-wrapper');
    
    if (banner) {
        // Visuelle Klassen entfernen
        banner.classList.remove('show');
        
        if (wrapper) {
            wrapper.classList.remove('blur-content');
        }
        
        // Event-Listener entfernen (Memory Leaks vermeiden)
        banner.removeEventListener('keydown', handleTabKey);
        banner.removeEventListener('focusin', trackFocus);
        document.removeEventListener('focusin', enforceFocus);
        document.removeEventListener('click', enforceFocus);
        
        // Optional: Fokus zurück auf den Auslöser setzen (z.B. Footer-Link),
        // falls wir wüssten, wer das war. Hier verzichten wir darauf.
    }
}

/**
 * Speichert das aktuell fokussierte Element.
 * Wird benötigt, um den Fokus zurückzusetzen, falls der Nutzer das Fenster verlässt.
 * 
 * @param {Event} e - Das Focus-Event.
 */
function trackFocus(e) {
    lastFocusedElement = e.target;
}

/**
 * Wächter-Funktion: Zwingt den Fokus zurück in das Banner.
 * Wird ausgelöst, wenn der Nutzer versucht, außerhalb des Modals zu klicken oder zu tabben.
 * 
 * @param {Event} e - Das Focus- oder Click-Event.
 */
function enforceFocus(e) {
    const banner = document.getElementById('cookie-banner');
    
    // Prüfen: Ist Banner offen UND liegt das Ziel des Events AUßERHALB des Banners?
    if (banner.classList.contains('show') && !banner.contains(e.target)) {
        e.stopPropagation();
        e.preventDefault();
        
        // Fokus zurück auf das letzte bekannte Element im Banner setzen
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        } else {
            // Fallback: Primary Button
            const primaryBtn = banner.querySelector('.btn--primary');
            if (primaryBtn) primaryBtn.focus();
        }
    }
}

/**
 * Verwaltet die Tab-Taste innerhalb des Banners (Circular Focus / Loop).
 * Verhindert, dass der Fokus beim Tabben das Modal verlässt.
 * 
 * @param {KeyboardEvent} e - Das Tastatur-Event.
 */
function handleTabKey(e) {
    const banner = document.getElementById('cookie-banner');
    // Alle fokussierbaren Elemente finden
    const focusables = banner.querySelectorAll('button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    
    if (focusables.length === 0) return;

    const firstElement = focusables[0];
    const lastElement = focusables[focusables.length - 1];

    if (e.key === 'Tab') {
        if (e.shiftKey) { 
            // Shift + Tab (Rückwärts): Wenn auf erstem Element -> Springe zu letztem
            if (document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            // Tab (Vorwärts): Wenn auf letztem Element -> Springe zu erstem
            if (document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }
}