import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
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
