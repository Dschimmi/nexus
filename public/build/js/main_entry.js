/* =========================================
   NEXUS FRONTEND ENTRY POINT
   Importiert alle CSS- und JS-Module für das Vite-Bundling.
   ========================================= */

// --- 1. CSS IMPORTS ---

// Layer 1: Globals
import '../css/variables.css';
import '../css/base.css'; // Enthält Reset

// Layer 2: Utilities
import '../css/utilities.css';

// Layer 3: Components
import '../css/components.css';

// Layer 4: Layout
import '../css/header.css';
import '../css/subheader.css';
import '../css/content.css';
import '../css/footer.css';

// Layer 5: Modules
import '../css/cookie-banner.css';
// admin.css entfernt, da durch Utilities ersetzt.

// --- 2. JS IMPORTS ---

import './layout.js';
import './theme.js';
import './compliance.js';
import './admin.js';