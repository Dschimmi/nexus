/**
 * DATEI: theme.js
 * ZWECK: Steuert den Dark-Mode Umschalter (Button).
 * Die Initialisierung passiert inline im Head (gegen Flackern).
 */

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('theme-toggle');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const html = document.documentElement;
            // Aktuellen Status prüfen
            const isDark = html.getAttribute('data-theme') === 'dark';
            // Wechseln
            const newTheme = isDark ? 'light' : 'dark';
            
            // Setzen & Speichern
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }
});