import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// Proxy target: no Docker use localhost:8002; dentro do Docker use backend:8000
const proxyTarget = process.env.PROXY_TARGET || 'http://localhost:8002'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    allowedHosts: ['visao.cfo.org.br'],
    watch: {
      usePolling: true
    },
    proxy: {
      '/api': {
        target: proxyTarget,
        changeOrigin: true,
        secure: false
      }
    }
  }
})
