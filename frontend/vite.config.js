import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import EnvValidator from './src/env'

export default defineConfig({
  plugins: [react()],
  server: {
    port: 3000,
    host: '0.0.0.0',
    allowedHosts: true
  }
})
