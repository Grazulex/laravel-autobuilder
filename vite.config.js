import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
    plugins: [vue()],
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                autobuilder: resolve(__dirname, 'resources/js/autobuilder.js'),
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name?.endsWith('.css')) {
                        return 'css/[name][extname]'
                    }
                    return 'assets/[name]-[hash][extname]'
                },
            },
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        origin: 'http://localhost:5173',
    },
})
