<?php

/**
 * German Translation File for Exelor
 * 
 * Format: 'key' => 'Translation', // Context for translators
 */
return [
    // --- GLOBAL ---
    'brand.name'   => 'Exelor',            // The name of the framework
    'brand.slogan' => 'Made it. Simple.',  // The corporate claim (always English)

    // ------------------------------
    // --- HEADER ---
    // ------------------------------
    'header.search_placeholder' => 'Suche...',      // Placeholder text inside the search input field
    'header.search_shortcut'    => 'Strg + K',      // Text indicating the keyboard shortcut for search
    'header.login'              => 'Login',         // Button label to sign in
    'header.register'           => 'Registrieren',  // Button label to sign up
    'header.greeting'           => 'Hallo, %name%', // Greeting for logged-in user (%name% is the username)
    'header.logout'             => 'Abmelden',      // Link to log out

    // ------------------------------
    // --- NAVIGATION (Subheader) ---
    // ------------------------------
    'nav.home'      => 'Startseite',   // Main link to homepage
    'nav.imprint'   => 'Impressum',    // Link to Legal Notice
    'nav.privacy'   => 'Datenschutz',  // Link to Privacy Policy

    // ------------------------------
    // --- Content ---
    // ------------------------------

    // --- HOMEPAGE: HERO SECTION
    'home.hero.title'     => 'Exelor – Modernes Web-Framework', // Main H1 headline on the landing page
    'home.hero.text'      => 'Exelor ist ein leichtgewichtiges, flexibles Web‑Framework für Entwickler, die schnell skalierbare Webanwendungen erstellen möchten.', // Sub-headline / introduction text
    'home.hero.btn_start' => 'Jetzt loslegen', // Primary CTA button (e.g. "Get Started")
    'home.hero.btn_docs'  => 'Dokumentation',  // Secondary button linking to documentation

    // --- HOMEPAGE: FEATURES SECTION ---
    'home.features.title' => 'Features', // Headline for the features grid

    'home.feat1.title' => 'Leistungsstark', // Title of feature 1 (Performance)
    'home.feat1.text'  => 'Minimaler Overhead, maximale Performance. Der Kern ist auf Geschwindigkeit optimiert.', // Description of feature 1

    'home.feat2.title' => 'Modular', // Title of feature 2 (Modularity)
    'home.feat2.text'  => 'Passe Exelor an deine Projekte an, integriere eigene Module oder Bibliotheken nahtlos.', // Description of feature 2

    'home.feat3.title' => 'Schnell', // Title of feature 3 (Development Speed)
    'home.feat3.text'  => 'Intuitive Syntax und umfangreiche Dokumentation beschleunigen die Umsetzung von Projekten.', // Description of feature 3

    'home.feat4.title' => 'Skalierbar', // Title of feature 4 (Scalability)
    'home.feat4.text'  => 'Ideal für kleine Projekte genauso wie für komplexe Anwendungen im Enterprise-Bereich.', // Description of feature 4

    // --- HOMEPAGE: INFO GRID ---
    'home.why.title' => 'Warum Exelor?', // Headline for "Why choose us" section
    'home.why.text1' => 'Exelor wurde entwickelt, um die Lücke zwischen klassischen Frameworks und modernen, leichten Entwicklungsumgebungen zu schließen.', // First paragraph "Why"
    'home.why.text2' => 'Mit Fokus auf Performance, Skalierbarkeit und klare Struktur hilft Exelor Teams, produktiver zu arbeiten.', // Second paragraph "Why"

    'home.docs.title' => 'Dokumentation & Community', // Headline for Docs & Community section
    'home.docs.text1' => 'Exelor bietet umfassende Dokumentation, Tutorials und Beispielprojekte, damit Entwickler sofort starten können.', // First paragraph Docs
    'home.docs.text2' => 'Zusätzlich wird eine Community aufgebaut, die Tipps, Erweiterungen und Best Practices austauscht.', // Second paragraph Docs
    'home.docs.link'  => 'Zur Community →', // Link text to the community page

    // --- HOMEPAGE: CTA (Call to Action) ---
    'home.cta.title' => 'Jetzt loslegen', // Section headline for final CTA

    'home.cta.col1.title' => 'Download', // Column 1 Title
    'home.cta.col1.text'  => 'Lade die aktuelle Version von Exelor herunter.', // Column 1 Text
    'home.cta.col1.btn'   => 'Download v1.0', // Column 1 Button Label

    'home.cta.col2.title' => 'Tutorials', // Column 2 Title
    'home.cta.col2.text'  => 'Schaue dir praxisnahe Anwendungsbeispiele an.', // Column 2 Text
    'home.cta.col2.btn'   => 'Zu den Beispielen', // Column 2 Button Label

    'home.cta.col3.title' => 'Kontakt', // Column 3 Title
    'home.cta.col3.text'  => 'Sende Ideen oder Fragen direkt an das Entwicklerteam.', // Column 3 Text
    'home.cta.col3.btn'   => 'Kontakt aufnehmen', // Column 3 Button Label

    // --- LOGIN FORM ---
    'login.username_label' => 'Benutzername oder E-Mail', // Label for the username input field, expecting Username or Email-Adress
    'login.password_label' => 'Passwort',                 // Label for the password input field
    'login.submit_btn'     => 'Anmelden',                 // Text on the submit button
    'login.error_auth'     => 'Ungültige Zugangsdaten.',  // Generic error message for failed login

    // --------------------
    // --- Admin Section
    // --------------------
    
    // --- ADMIN DASHBOARD ---
    'admin.dashboard_title'   => 'Admin Dashboard',           // Title of the admin page
    'admin.welcome'           => 'Willkommen, %name%',        // Greeting with username
    'admin.logout_btn'        => 'Abmelden',                  // Logout button text
    'admin.modules_title'     => 'Module konfigurieren',      // Heading for the config section
    'admin.module_user'       => 'User-Verwaltung aktivieren',// Label for user module toggle
    'admin.module_search'     => 'Suchfunktion aktivieren',   // Label for search module toggle
    'admin.module_cookie'     => 'Cookie-Banner aktivieren',  // Label for cookie banner toggle
    'admin.module_lang'       => 'Sprachauswahl aktivieren',  // Label for language toggle
    'admin.save_btn'          => 'Einstellungen speichern',   // Save button text
    'admin.pages_title'       => 'Seiten-Verwaltung',         // Heading for pages section
    'admin.pages_create_link' => 'Neue Dummy-Seite erstellen',// Link to create a new page

    // --- ADMIN PAGE CREATION ---
    'admin.page_create_title'  => 'Neue Seite erstellen',      // Title of the creation form
    'admin.page_label_title'   => 'Seitentitel',               // Label for page title input
    'admin.page_label_slug'    => 'URL-Slug (z.B. meine-seite)', // Label for URL slug input
    'admin.page_label_content' => 'Inhalt (HTML erlaubt)',     // Label for content textarea
    'admin.page_btn_save'      => 'Seite veröffentlichen',     // Submit button text
    'admin.page_success'       => 'Seite erfolgreich erstellt.', // Success flash message
    'admin.page_error'         => 'Fehler beim Erstellen der Seite.', // Generic error message

    // --------------
    // --- FOOTER ---
    // --------------
    'footer.copyright'       => '© %year% Exelor Framework. Alle Rechte vorbehalten.', // Copyright notice (%year% is replaced dynamically)
    'footer.legal'           => 'Rechtliches',              // Heading for the legal links section in the footer
    'footer.imprint'         => 'Impressum',                // Link label to the Legal Notice / Imprint page
    'footer.privacy'         => 'Datenschutz',              // Link label to the Privacy Policy page
    'footer.cookie_settings' => 'Cookie-Einstellungen',     // Link text to reopen the cookie consent settings modal
    'footer.sitemap'         => 'Sitemap',                  // Link label to the Sitemap page

    // --- IMPRESSUM (Rechtliche Angaben) ---
    'imprint.section_tmg'     => 'Angaben gemäß § 5 TMG', // Heading for the legal disclosure section
    'imprint.address_block'   => "Max Mustermann\nMusterstraße 1\n12345 Musterstadt", // Adresse mit \n für sicheres nl2br
    'imprint.section_contact' => 'Kontakt', // Heading for the contact section
    'imprint.phone'           => 'Telefon: +49 (0) 123 44 55 66', // Phone number line
    'imprint.email'           => 'E-Mail: info@exelor.de', // Email address line
    
    // --- DATENSCHUTZ ---
    'privacy.intro_title'     => '1. Datenschutz auf einen Blick', // Section 1 Title
    'privacy.general_title'   => 'Allgemeine Hinweise', // General info subtitle
    'privacy.general_text'    => 'Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen.', // General info text
    'privacy.collection_title'=> 'Datenerfassung auf dieser Website', // Data collection subtitle
    'privacy.responsible_q'   => 'Wer ist verantwortlich für die Datenerfassung auf dieser Website?', // Question regarding responsibility
    'privacy.responsible_a'   => 'Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber. Dessen Kontaktdaten können Sie dem Impressum dieser Website entnehmen.', // Answer regarding responsibility

    // --- COOKIE BANNER ---
    'cookie.title'         => 'Datenschutzeinstellungen', // Title of the cookie modal
    'cookie.text'          => 'Wir nutzen Cookies, um die technische Funktionalität unserer Website sicherzustellen ("Essentiell"). Mit Ihrer Zustimmung nutzen wir zudem Cookies, um die Nutzung unserer Seite zu analysieren und unser Angebot zu verbessern. Weitere Informationen finden Sie in unserer <a href="/datenschutz">Datenschutzerklärung</a> und im <a href="/impressum">Impressum</a>.', // Legal disclaimer text containing HTML links
    'cookie.btn_accept'    => 'Alle akzeptieren', // Button to accept all cookies
    'cookie.btn_essential' => 'Nur Essentielle',  // Button to accept only necessary cookies

    // --- SYSTEM / ERRORS ---
    'error.404.text'       => 'Seite nicht gefunden. Sie werden weitergeleitet...', // Message shown before redirecting from a 404 page

    // --- KONTAKTSEITE ---
        'contact.title'    => 'Kontakt',                    // H1 Title
        'contact.intro'    => 'Haben Sie Fragen oder Anregungen? Wir freuen uns auf Ihre Nachricht.', // Intro text
        'contact.email'    => 'E-Mail',                     // Label for Email
        'contact.phone'    => 'Telefon',                    // Label for Phone
        'contact.address'  => 'Anschrift',                  // Label for Address
        'contact.cta'      => 'Schreiben Sie uns',          // Button or call to action text    
];