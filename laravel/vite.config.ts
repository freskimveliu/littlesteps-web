import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'node:path';

const projectRoot = import.meta.dirname;

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.ts'],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            tailwindcss(),
        ],
        resolve: {
            alias: {
                '@': resolve(projectRoot, 'resources/js'),
            },
        },
        server: {
            host: '0.0.0.0',
            port: 5173,
            // Requests arrive via Traefik, so the proxied hostnames have to be
            // allowed explicitly or Vite rejects them with a 403.
            allowedHosts: [env.VITE_HMR_HOST || 'localhost'],
            // Traefik terminates TLS in front of the dev server, so the browser
            // must be told to open the HMR socket over wss on the proxy's port.
            hmr: {
                host: env.VITE_HMR_HOST || 'localhost',
                protocol: 'wss',
                clientPort: Number(env.VITE_HMR_CLIENT_PORT || 443),
            },
            watch: {
                usePolling: true,
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
