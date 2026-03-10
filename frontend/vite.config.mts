import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import { resolve } from 'node:path';

// Vite config so that `npm run build` outputs into CI4's `public/react` folder
// with a stable main entry file name that CI4 views can reference.
export default defineConfig({
  plugins: [react()],
  root: resolve(__dirname),
  base: '/react/',
  build: {
    outDir: resolve(__dirname, '../public/react'),
    emptyOutDir: true,
    rollupOptions: {
      input: resolve(__dirname, 'src/main.tsx'),
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]'
      }
    }
  }
});

