import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  // Basis-Pfad für die generierten Dateien im Browser
  base: '/build/',
  
  build: {
    // Wohin sollen die fertigen Dateien?
    outDir: 'public/build',
    
    // Wichtig: Manifest erzeugen, damit PHP die Dateien findet
    manifest: true,
    
    // Alten Build-Ordner vorher leeren
    emptyOutDir: true,
    
    rollupOptions: {
      // Hier listen wir ALLE unsere atomaren Dateien auf
      input: {
        // CSS
        variables: 'public/css/variables.css',
        base: 'public/css/base.css',
        header: 'public/css/header.css',
        subheader: 'public/css/subheader.css',
        content: 'public/css/content.css',
        footer: 'public/css/footer.css',
        cookie: 'public/css/cookie-banner.css',
        
        // JS
        layout: 'public/js/layout.js',
        compliance: 'public/js/compliance.js',
        theme: 'public/js/theme.js',
      },
    },
  },
});