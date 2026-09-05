import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// Production builds REQUIRE VITE_API_BASE_URL so the deployed SPA talks to a
// real API. The old env.ts guard never actually failed the build (modules are
// not executed during bundling) — it only threw at runtime, producing a
// permanent white page. This build-time gate makes the failure loud, early,
// and fixable before anything is deployed.
import path from 'path'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  if (mode === 'production' && !env.VITE_API_BASE_URL) {
    throw new Error(
      'Missing required environment variable: VITE_API_BASE_URL. ' +
        'For a production build, set it before building, e.g. ' +
        'VITE_API_BASE_URL=https://eventiq-api.onrender.com npm run build'
    )
  }
  return {
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'src')
      }
    },
    plugins: [react()],
  server: {
    port: 3000,
    host: '0.0.0.0',
    allowedHosts: true,
    proxy: {
      // Forward API requests through the dev server so same-origin cookies
      // and CORS behave consistently during local development.
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      // Sanctum's csrf-cookie route is registered at /sanctum/csrf-cookie
      // (without /api prefix). The SPA's axios baseURL includes /api, so
      // requests go to /api/sanctum/csrf-cookie. This proxy strips the
      // /api prefix and forwards to the backend's /sanctum path.
      '/sanctum': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, ''),
      },
    },
  },
  build: {
    chunkSizeWarningLimit: 1000,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('react-dom') || id.includes('react-router') || id.includes('/react/')) return 'vendor-react'
            if (id.includes('@tanstack')) return 'vendor-tanstack'
            // recharts is a React component library that calls hooks
            // internally -- grouping it into vendor-heavy alongside
            // firebase/date-fns (neither of which touch React at all) put
            // it in a separate chunk from vendor-react. That's the classic
            // Vite/Rollup pitfall for "Cannot read properties of undefined
            // (reading 'useState')": recharts ends up evaluating before
            // vendor-react has initialized, or resolving against a second,
            // undefined React reference across the chunk boundary. Confirmed
            // via a real headless-browser load of the built app -- the
            // error's stack trace pointed directly at vendor-heavy.
            // firebase/date-fns have no such dependency and are safe to
            // keep isolated.
            if (id.includes('firebase') || id.includes('@firebase') || id.includes('date-fns')) return 'vendor-heavy'
            // let other deps stay with the chunks that import them (better code-splitting)
          }
        }
      }
    }
  }
  }
})
