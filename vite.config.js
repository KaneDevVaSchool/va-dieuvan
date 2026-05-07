import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        VitePWA({
            registerType: 'prompt',
            injectRegister: false,
            scope: '/',
            includeAssets: ['favicon.ico', 'robots.txt', 'icons/*.png'],
            manifest: {
                name: 'VAS Dispatch',
                short_name: 'Điều Vận',
                description:
                    'Điều vận VAS — mở nhanh, làm việc mọi lúc.',
                theme_color: '#020B0B',
                background_color: '#020B0B',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '/',
                scope: '/',
                lang: 'vi',
                icons: [
                    {
                        src: '/icons/pwa-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/pwa-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/pwa-512-maskable.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,woff2,png,svg,ico}'],
                additionalManifestEntries: [
                    { url: '/', revision: `laravel-shell-${Date.now()}` },
                ],
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.(googleapis|gstatic)\.com/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts',
                            expiration: {
                                maxEntries: 20,
                                maxAgeSeconds: 60 * 60 * 24 * 365,
                            },
                        },
                    },
                    {
                        urlPattern:
                            /\/api\/(targets|config|lookup|reference-pricing)\b/,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'api-lookup',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 60 * 24,
                            },
                        },
                    },
                ],
                navigateFallback: '/',
                navigateFallbackDenylist: [/^\/api\//],
                skipWaiting: false,
                clientsClaim: true,
            },
            devOptions: {
                enabled: true,
                type: 'module',
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('vue-router')) return 'vue-router';
                    if (id.includes('pinia')) return 'pinia';
                    if (id.includes('vue-i18n')) return 'vue-i18n';
                    if (id.includes('echarts')) return 'echarts';
                    if (id.includes('@heroicons')) return 'icons';
                    if (id.includes('axios')) return 'axios';
                    if (id.includes('vue')) return 'vue-vendor';
                },
            },
        },
    },
});
