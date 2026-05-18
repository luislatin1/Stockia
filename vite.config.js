import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const devPort = Number(env.VITE_PORT || 5173);

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
        server: {
            host: env.VITE_HOST || '127.0.0.1',
            port: devPort,
            strictPort: true,
            hmr: {
                host: env.VITE_HMR_HOST || env.VITE_HOST || '127.0.0.1',
                port: Number(env.VITE_HMR_PORT || devPort),
            },
        },
    };
});
