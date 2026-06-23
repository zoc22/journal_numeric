import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'
import { fileURLToPath } from 'url'
import process from 'node:process' // 1. Règle le problème de "process is not defined"

// 2. Règle le problème de "__dirname is not defined"
const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

export default defineConfig(({ mode }) => {
  // 3. Permet à Vite de lire correctement ton fichier .env et VITE_API_URL
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [react(),tailwindcss()],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
    },
    server: {
      port: 3000,
      proxy: {
        '/api': {
          target: env.VITE_API_URL || 'http://localhost:8000',
          changeOrigin: true,
        },
      },
    },
  }
})