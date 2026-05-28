import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import dts from 'vite-plugin-dts';
import { resolve } from 'path';

export default defineConfig({
  plugins: [
    react(),
    dts({ include: ['src'], outDir: 'dist' }),
  ],
  build: {
    lib: {
      entry:    resolve(__dirname, 'src/index.tsx'),
      name:     'Ci4ReactCrud',
      formats:  ['es', 'cjs'],
      fileName: 'index',
    },
    rollupOptions: {
      external: ['react', 'react-dom', 'react/jsx-runtime'],
      output: {
        globals: {
          react:           'React',
          'react-dom':     'ReactDOM',
          'react/jsx-runtime': 'ReactJSXRuntime',
        },
        assetFileNames: 'style.css',
      },
    },
    sourcemap: true,
  },
  test: {
    globals:     true,
    environment: 'jsdom',
    setupFiles:  './src/test-setup.ts',
  },
});
