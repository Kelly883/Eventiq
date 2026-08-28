import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// Production builds REQUIRE VITE_API_BASE_URL so the deployed SPA talks to a
// real API. The old env.ts guard never actually failed the build (modules are
// not executed during bundling) — it only threw at runtime, producing a
// permanent white page. This build-time gate makes the failure loud, early,
// and fixable before anything is deployed.
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
    plugins: [react()],
  server: {
    port: 3000,
    host: '0.0.0.0',
    allowedHosts: true
  },
  build: {
    chunkSizeWarningLimit: 1000,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('react-dom') || id.includes('react-router') || id.includes('/react/')) return 'vendor-react'
            if (id.includes('@tanstack')) return 'vendor-tanstack'
            if (id.includes('recharts') || id.includes('firebase') || id.includes('@firebase') || id.includes('date-fns')) return 'vendor-heavy'
            // let other deps stay with the chunks that import them (better code-splitting)
          }
        }
      }
    }
  }
  }
})
