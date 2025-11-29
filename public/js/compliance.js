/**
 * DATEI: compliance.js
 * ZWECK: Steuert Cookie-Banner, Blur-Effekt und "intelligente" Focus-Trap.
 */

// Speichert, wo der Nutzer zuletzt im Banner war
let lastFocusedElement = null;

document.addEventListener('DOMContentLoaded', () => {
    const consent = localStorage.getItem('cookie_consent');
    if (!consent) {
        showBanner();
    }
});

// Globale Funktion für Buttons
window.handleCookie = function(accepted) {
    const value = accepted ? 'all' : 'essential';
    localStorage.setItem('cookie_consent', value);
    hideBanner();
    console.log('Compliance: Cookies gesetzt auf:', value);
};

// --- Helper Funktionen ---

function showBanner() {
    const banner = document.getElementById('cookie-banner');
    const body = document.body;
    
    if (banner) {
        banner.classList.add('active');
        body.classList.add('cookie-open');
        
        // 1. Initialer Fokus auf den Haupt-Button
        const primaryBtn = banner.querySelector('.btn-primary');
        if (primaryBtn) {
            primaryBtn.focus();
            lastFocusedElement = primaryBtn; // Startpunkt merken
        }
        
        // 2. Listener aktivieren
        banner.addEventListener('keydown', handleTabKey);
        
        // NEU: Merken, wo der Fokus im Banner hingeht
        banner.addEventListener('focusin', trackFocus);
        
        // Wächter gegen Ausbrechen
        document.addEventListener('focusin', enforceFocus);
        document.addEventListener('click', enforceFocus);
    }
}

function hideBanner() {
    const banner = document.getElementById('cookie-banner');
    const body = document.body;
    
    if (banner) {
        banner.classList.remove('active');
        body.classList.remove('cookie-open');
        
        // Aufräumen
        banner.removeEventListener('keydown', handleTabKey);
        banner.removeEventListener('focusin', trackFocus);
        document.removeEventListener('focusin', enforceFocus);
        document.removeEventListener('click', enforceFocus);
    }
}

/**
 * NEU: Aktualisiert die Merk-Variable, wenn der Nutzer im Banner tabbt/klickt.
 */
function trackFocus(e) {
    lastFocusedElement = e.target;
}

/**
 * Zwingt den Fokus zurück zum ZULETZT aktiven Element im Banner.
 */
function enforceFocus(e) {
    const banner = document.getElementById('cookie-banner');
    
    // Wenn wir versuchen, das aktive Banner zu verlassen
    if (banner.classList.contains('active') && !banner.contains(e.target)) {
        e.stopPropagation();
        e.preventDefault();
        
        // Zurück zum letzten bekannten Element (oder Fallback auf Primary)
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        } else {
            const primaryBtn = banner.querySelector('.btn-primary');
            if (primaryBtn) primaryBtn.focus();
        }
    }
}

/**
 * Hält den Fokus beim Tabben im Kreis (Loop).
 */
function handleTabKey(e) {
    const banner = document.getElementById('cookie-banner');
    const focusables = banner.querySelectorAll('button, a[href]');
    
    if (focusables.length === 0) return;

    const firstElement = focusables[0];
    const lastElement = focusables[focusables.length - 1];

    if (e.key === 'Tab') {
        if (e.shiftKey) { 
            if (document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            if (document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }
}