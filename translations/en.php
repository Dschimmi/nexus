<?php

/**
 * English Translation File for Exelor
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
    'header.search_placeholder' => 'Search...',         // Placeholder text inside the search input field
    'header.search_shortcut'    => 'Ctrl + K',          // Text indicating the keyboard shortcut for search
    'header.login'              => 'Login',             // Button label to sign in
    'header.register'           => 'Sign up',           // Button label to sign up
    'header.greeting'           => 'Hello, %name%',     // Greeting for logged-in user (%name% is the username)
    'header.logout'             => 'Logout',            // Link to log out

    // ------------------------------
    // --- NAVIGATION (Subheader) ---
    // ------------------------------
    'nav.home'      => 'Home',          // Main link to homepage
    'nav.imprint'   => 'Legal Notice',  // Link to Legal Notice
    'nav.privacy'   => 'Privacy',       // Link to Privacy Policy

    // ------------------------------
    // --- Content ---
    // ------------------------------

    // --- HOMEPAGE: HERO SECTION
    'home.hero.title'     => 'Exelor – Modern Web Framework', // Main H1 headline on the landing page
    'home.hero.text'      => 'Exelor is a lightweight, flexible web framework for developers who want to build scalable web applications quickly.', // Sub-headline / introduction text
    'home.hero.btn_start' => 'Get Started',    // Primary CTA button (e.g. "Get Started")
    'home.hero.btn_docs'  => 'Documentation',  // Secondary button linking to documentation

    // --- HOMEPAGE: FEATURES SECTION ---
    'home.features.title' => 'Features', // Headline for the features grid

    'home.feat1.title' => 'High Performance', // Title of feature 1 (Performance)
    'home.feat1.text'  => 'Minimal overhead, maximum performance. The core is optimized for speed.', // Description of feature 1

    'home.feat2.title' => 'Modular', // Title of feature 2 (Modularity)
    'home.feat2.text'  => 'Adapt Exelor to your projects, integrate your own modules or libraries seamlessly.', // Description of feature 2

    'home.feat3.title' => 'Fast', // Title of feature 3 (Development Speed)
    'home.feat3.text'  => 'Intuitive syntax and comprehensive documentation accelerate project implementation.', // Description of feature 3

    'home.feat4.title' => 'Scalable', // Title of feature 4 (Scalability)
    'home.feat4.text'  => 'Ideal for small projects as well as complex enterprise applications.', // Description of feature 4

    // --- HOMEPAGE: INFO GRID ---
    'home.why.title' => 'Why Exelor?', // Headline for "Why choose us" section
    'home.why.text1' => 'Exelor was developed to bridge the gap between classic frameworks and modern, lightweight development environments.', // First paragraph "Why"
    'home.why.text2' => 'With a focus on performance, scalability, and clear structure, Exelor helps teams work more productively.', // Second paragraph "Why"

    'home.docs.title' => 'Documentation & Community', // Headline for Docs & Community section
    'home.docs.text1' => 'Exelor offers comprehensive documentation, tutorials, and example projects so developers can start immediately.', // First paragraph Docs
    'home.docs.text2' => 'Additionally, a community is being built to exchange tips, extensions, and best practices.', // Second paragraph Docs
    'home.docs.link'  => 'Join Community →', // Link text to the community page

    // --- HOMEPAGE: CTA (Call to Action) ---
    'home.cta.title' => 'Get Started Now', // Section headline for final CTA

    'home.cta.col1.title' => 'Download', // Column 1 Title
    'home.cta.col1.text'  => 'Download the current version of Exelor.', // Column 1 Text
    'home.cta.col1.btn'   => 'Download v1.0', // Column 1 Button Label

    'home.cta.col2.title' => 'Tutorials', // Column 2 Title
    'home.cta.col2.text'  => 'Check out practical application examples.', // Column 2 Text
    'home.cta.col2.btn'   => 'View Examples', // Column 2 Button Label

    'home.cta.col3.title' => 'Contact', // Column 3 Title
    'home.cta.col3.text'  => 'Send ideas or questions directly to the development team.', // Column 3 Text
    'home.cta.col3.btn'   => 'Contact Us', // Column 3 Button Label

    // --- LOGIN FORM ---
    'login.username_label' => 'Username or Email',        // Label for the username input field, expecting Username or Email-Adress
    'login.password_label' => 'Password',                 // Label for the password input field
    'login.submit_btn'     => 'Sign In',                  // Text on the submit button
    'login.error_auth'     => 'Invalid credentials.',     // Generic error message for failed login

    // --------------------
    // --- Admin Section
    // --------------------
    
    // --- ADMIN DASHBOARD ---
    'admin.dashboard_title'   => 'Admin Dashboard',           // Title of the admin page
    'admin.welcome'           => 'Welcome, %name%',           // Greeting with username
    'admin.logout_btn'        => 'Logout',                    // Logout button text
    'admin.modules_title'     => 'Configure Modules',         // Heading for the config section
    'admin.module_user'       => 'Enable User Management',    // Label for user module toggle
    'admin.module_search'     => 'Enable Search Function',    // Label for search module toggle
    'admin.module_cookie'     => 'Enable Cookie Banner',      // Label for cookie banner toggle
    'admin.module_lang'       => 'Enable Language Selection', // Label for language toggle
    'admin.save_btn'          => 'Save Settings',             // Save button text
    'admin.pages_title'       => 'Page Management',           // Heading for pages section
    'admin.pages_create_link' => 'Create New Dummy Page',     // Link to create a new page
    'admin.pages_btn_manage'  => 'Manage Pages',              // Button to go to list view

    // --- ADMIN PAGE CREATION ---
    'admin.page_create_title'  => 'Create New Page',           // Title of the creation form
    'admin.page_label_title'   => 'Page Title',                // Label for page title input
    'admin.page_label_slug'    => 'URL Slug (e.g. my-page)',   // Label for URL slug input
    'admin.page_label_content' => 'Content (HTML allowed)',    // Label for content textarea
    'admin.page_btn_save'      => 'Publish Page',              // Submit button text
    'admin.page_success'       => 'Page created successfully.',// Success flash message
    'admin.page_error'         => 'Error creating page.',      // Generic error message

    // --- ADMIN PAGE MANAGEMENT ---
    'admin.pages_list_title'   => 'Manage Pages',              // Headline for the list view
    'admin.pages_col_select'   => 'Select',                    // Column header for checkboxes
    'admin.pages_col_title'    => 'Title',                     // Column header for page title
    'admin.pages_col_slug'     => 'URL Path',                  // Column header for slug
    'admin.pages_btn_create'   => 'Create New Page',           // Button to go to create form
    'admin.pages_btn_delete'   => 'Delete Selected',           // Delete action button
    'admin.pages_empty'        => 'No pages available.',       // Text when list is empty
    'admin.pages_delete_ok'    => 'Page(s) deleted successfully.', // Success flash message
    'admin.pages_delete_err'   => 'Error deleting pages.',     // Error flash message

    // --------------
    // --- FOOTER ---
    // --------------
    'footer.copyright'       => '© %year% Exelor Framework. All rights reserved.', // Copyright notice (%year% is replaced dynamically)
    'footer.legal'           => 'Legal',                    // Heading for the legal links section in the footer
    'footer.imprint'         => 'Legal Notice',             // Link label to the Legal Notice / Imprint page
    'footer.privacy'         => 'Privacy Policy',           // Link label to the Privacy Policy page
    'footer.cookie_settings' => 'Cookie Settings',          // Link text to reopen the cookie consent settings modal
    'footer.sitemap'         => 'Sitemap',                  // Link label to the Sitemap page

    // --- IMPRESSUM (Rechtliche Angaben) ---
    // Note: Legal information often stays in the original language or uses specific legal terms.
    'imprint.section_tmg'     => 'Information according to § 5 TMG', // Heading for the legal disclosure section
    'imprint.address_block'   => "Max Mustermann\nMusterstraße 1\n12345 Musterstadt\nGermany", // Address block
    'imprint.section_contact' => 'Contact', // Heading for the contact section
    'imprint.phone'           => 'Phone: +49 (0) 123 44 55 66', // Phone number line
    'imprint.email'           => 'Email: info@exelor.de', // Email address line
    
    // --- DATENSCHUTZ ---
    'privacy.intro_title'     => '1. Data Protection at a Glance', // Section 1 Title
    'privacy.general_title'   => 'General Information', // General info subtitle
    'privacy.general_text'    => 'The following notes provide a simple overview of what happens to your personal data when you visit this website.', // General info text
    'privacy.collection_title'=> 'Data Collection on this Website', // Data collection subtitle
    'privacy.responsible_q'   => 'Who is responsible for data collection on this website?', // Question regarding responsibility
    'privacy.responsible_a'   => 'Data processing on this website is carried out by the website operator. You can find their contact details in the Legal Notice of this website.', // Answer regarding responsibility

    // --- COOKIE BANNER ---
    'cookie.title'         => 'Privacy Settings', // Title of the cookie modal
    'cookie.text'          => 'We use cookies to ensure the technical functionality of our website ("Essential"). With your consent, we also use cookies to analyze the usage of our site and improve our offer. Further information can be found in our <a href="/datenschutz">Privacy Policy</a> and <a href="/impressum">Legal Notice</a>.', // Legal disclaimer text containing HTML links
    'cookie.btn_accept'    => 'Accept All',       // Button to accept all cookies
    'cookie.btn_essential' => 'Essential Only',   // Button to accept only necessary cookies

    // --- SYSTEM / ERRORS ---
    'error.404.text'       => 'Page not found. Redirecting...', // Message shown before redirecting from a 404 page

    // --- KONTAKTSEITE ---
    'contact.title'    => 'Contact',                    // H1 Title
    'contact.intro'    => 'Do you have questions or suggestions? We look forward to your message.', // Intro text
    'contact.email'    => 'Email',                      // Label for Email
    'contact.phone'    => 'Phone',                      // Label for Phone
    'contact.address'  => 'Address',                    // Label for Address
    'contact.cta'      => 'Write to us',                // Button or call to action text    
];