import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
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
})
