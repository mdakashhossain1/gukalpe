import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    // Relative (not absolute-root) so any url()s baked into the built CSS
    // (e.g. bootstrap-icons' @font-face src) resolve against the CSS
    // file's own folder rather than the domain root - this app is served
    // from a nested subdirectory (see index.php), and an absolute `/build/...`
    // reference would 404 since the browser resolves it against the host
    // root, never reaching that subdirectory at all. Doesn't affect the
    // <link>/<script> tags themselves - those go through Laravel's own
    // asset() (see @vite() in layouts/app.blade.php), which is already
    // request-aware and unrelated to this setting.
    base: './',
    plugins: [
        laravel({
            input: ['resources/css/app.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
