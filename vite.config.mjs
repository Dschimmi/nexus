import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  base: '/build/',
  
  build: {
    outDir: 'public/build',
    manifest: true,
    emptyOutDir: true,
    
    rollupOptions: {
      // Single Entry Point -> Erzeugt ein Bundle (app.js + app.css)
      input: {
        app: 'public/js/main_entry.js',
      },
    },
  },
});