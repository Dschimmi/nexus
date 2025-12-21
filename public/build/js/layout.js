/**
 * Layout Logic
 * Enthält alle globalen UI-Interaktionen:
 * - Subheader Scroll (Desktop/Mobile)
 * - Dropdown Toggle (Touch-Support)
 * - Alert Dismissal (Click & Auto-Fade)
 * - Sticky Header Shadow (Visual Feedback)
 * - Search Shortcut (Accessibility)
 */
document.addEventListener('DOMContentLoaded', () => {
    initSubheaderScroll();
    initDropdowns();
    initAlerts();
    initStickyHeader();
});

/**
 * 1. Subheader Horizontal Scroll
 */
function initSubheaderScroll() {
    const wrapper = document.getElementById('nav-scroll-wrapper');
    const btnLeft = document.getElementById('nav-scroll-left');
    const btnRight = document.getElementById('nav-scroll-right');

    if (!wrapper || !btnLeft || !btnRight) return;

    const scrollAmount = 200;

    btnLeft.addEventListener('click', () => {
        wrapper.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });

    btnRight.addEventListener('click', () => {
        wrapper.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });

    const updateArrows = () => {
        // Toleranz für Zoom-Stufen / High-DPI
        const scrollLeft = Math.ceil(wrapper.scrollLeft);
        const maxScroll = Math.ceil(wrapper.scrollWidth - wrapper.clientWidth);

        if (scrollLeft > 0) {
            btnLeft.classList.remove('subheader__arrow--hidden');
        } else {
            btnLeft.classList.add('subheader__arrow--hidden');
        }

        if (scrollLeft < maxScroll) {
            btnRight.classList.remove('subheader__arrow--hidden');
        } else {
            btnRight.classList.add('subheader__arrow--hidden');
        }
    };

    wrapper.addEventListener('scroll', updateArrows);
    window.addEventListener('resize', updateArrows);
    
    // Initiale Berechnung
    setTimeout(updateArrows, 50);
}

/**
 * 2. Dropdown Logic
 * Klick-Support für Touch-Devices und Tastatur-Nutzer.
 */
function initDropdowns() {
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.dropdown__trigger');
        const dropdown = trigger ? trigger.closest('.dropdown') : null;

        // A. Klick auf Trigger -> Toggle
        if (dropdown) {
            const isOpen = dropdown.classList.contains('dropdown--open');
            closeAllDropdowns(); // Andere schließen (Accordion-Effekt)

            if (!isOpen) {
                dropdown.classList.add('dropdown--open');
            }
        } 
        // B. Klick außerhalb -> Schließen
        else if (!e.target.closest('.dropdown__menu')) {
            closeAllDropdowns();
        }
    });

    // Schließen bei ESC-Taste
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllDropdowns();
    });
}

function closeAllDropdowns() {
    document.querySelectorAll('.dropdown--open').forEach(el => {
        el.classList.remove('dropdown--open');
    });
}

/**
 * 3. Alert Management
 * - Schließen per Klick
 * - Automatisches Ausblenden von Erfolgsmeldungen nach 5s
 */
function initAlerts() {
    // A. Click Dismiss
    document.addEventListener('click', (e) => {
        const closeBtn = e.target.closest('.alert button, [data-dismiss="alert"]');
        if (closeBtn) {
            const alert = closeBtn.closest('.alert');
            if (alert) alert.remove();
        }
    });

    // B. Auto Dismiss (nur für Success-Meldungen)
    const autoDismissAlerts = document.querySelectorAll('.alert--success');
    if (autoDismissAlerts.length > 0) {
        setTimeout(() => {
            autoDismissAlerts.forEach(alert => {
                // Fade-Out Effekt via CSS Transition vorbereiten
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                
                // Nach Transition entfernen
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000); // 5 Sekunden Wartezeit
    }
}

/**
 * 4. Sticky Header Shadow
 * Fügt dem Header einen Schatten hinzu, wenn gescrollt wird.
 */
function initStickyHeader() {
    const header = document.querySelector('.header');
    if (!header) return;

    const toggleShadow = () => {
        if (window.scrollY > 10) {
            header.classList.add('header--scrolled');
        } else {
            header.classList.remove('header--scrolled');
        }
    };

    window.addEventListener('scroll', toggleShadow);
    toggleShadow(); // Initial check
}

/* =========================================
   GLOBAL SHORTCUTS
   ========================================= */

document.addEventListener('keydown', function(event) {
    // Strg + K (oder Cmd + K auf Mac) abfangen
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        const searchInput = document.querySelector('.header__search-input');
        
        // Nur wenn das Suchfeld tatsächlich existiert (Modul aktiviert)
        if (searchInput) {
            event.preventDefault(); // Browser-Verhalten unterdrücken
            searchInput.focus();    // Fokus in unser Feld setzen
        }
    }
});