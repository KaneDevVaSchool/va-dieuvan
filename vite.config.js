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
            strategies: 'injectManifest',
            srcDir: 'resources/js/src',
            filename: 'sw.js',
            registerType: 'autoUpdate',
            injectRegister: false,
            scope: '/',
            includeAssets: ['favicon.ico', 'robots.txt', 'icons/*.png'],
            injectManifest: {
                globPatterns: ['**/*.{js,css,html,woff2,png,svg,ico}'],
                globIgnores: [
                    '**/*.map',
                    '**/workbox-*.js',
                    'sw.js',
                ],
                // Skip any single file larger than 3 MB; it will still load from the network.
                maximumFileSizeToCacheInBytes: 3 * 1024 * 1024,
                additionalManifestEntries: [
                    { url: '/', revision: `laravel-shell-${Date.now()}` },
                ],
            },
            manifest: {
                id: '/',
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
                shortcuts: [
                    {
                        name: 'Chuyến của tôi',
                        short_name: 'Chuyến',
                        description: 'Mở trang tài xế',
                        url: '/driver',
                        icons: [
                            {
                                src: '/icons/pwa-192.png',
                                sizes: '192x192',
                                type: 'image/png',
                            },
                        ],
                    },
                    {
                        name: 'Lịch',
                        short_name: 'Lịch',
                        description: 'Lịch chuyến tài xế',
                        url: '/driver/schedule',
                        icons: [
                            {
                                src: '/icons/pwa-192.png',
                                sizes: '192x192',
                                type: 'image/png',
                            },
                        ],
                    },
                ],
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
                    // Heavy charting library — split off so driver shell never loads it
                    if (id.includes('echarts') || id.includes('zrender')) return 'echarts';
                    // Workbox runtime (separate from the precache manifest)
                    if (id.includes('workbox')) return 'workbox';
                    // Core framework pieces — minimal, loaded on every page
                    if (id.includes('vue-router')) return 'vue-router';
                    if (id.includes('pinia')) return 'pinia';
                    if (id.includes('vue-i18n')) return 'vue-i18n';
                    if (id.includes('@heroicons')) return 'icons';
                    if (id.includes('axios')) return 'axios';
                    if (id.includes('vue')) return 'vue-vendor';
                },
            },
        },
    },
});
