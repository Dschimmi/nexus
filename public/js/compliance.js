/**
 * DATEI: cookie-consent.js
 * ZWECK: Steuert das Ein-/Ausblenden des Cookie-Banners und den Blur-Effekt.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Prüfen, ob der Nutzer schon entschieden hat
    const consent = localStorage.getItem('cookie_consent');
    
    // Wenn nein: Banner anzeigen
    if (!consent) {
        showBanner();
    }
});

// Globale Funktion für die Buttons (wird im HTML via onclick aufgerufen)
window.handleCookie = function(accepted) {
    // 1. Speichern (Simuliert)
    const value = accepted ? 'all' : 'essential';
    localStorage.setItem('cookie_consent', value);
    
    // 2. Banner ausblenden
    hideBanner();
    
    // 3. Optional: Reload oder Event feuern
    console.log('Cookies gesetzt auf:', value);
};

// --- Helper Funktionen ---

function showBanner() {
    const banner = document.getElementById('cookie-banner');
    const body = document.body;
    
    if (banner) {
        banner.classList.add('active');
        // Fügt den Blur-Effekt auf der Seite hinzu (definiert in base.css)
        body.classList.add('cookie-open');
    }
}

function hideBanner() {
    const banner = document.getElementById('cookie-banner');
    const body = document.body;
    
    if (banner) {
        banner.classList.remove('active');
        // Entfernt den Blur-Effekt
        body.classList.remove('cookie-open');
    }
}