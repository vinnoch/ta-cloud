import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.Pusher = Pusher;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;

if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
const pageUsesHttps = window.location.protocol === 'https:';
const reverbUsesHttps = reverbScheme === 'https';
const shouldBootEcho = reverbKey && !(pageUsesHttps && !reverbUsesHttps);

if (shouldBootEcho) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
        forceTLS: reverbUsesHttps,
        enabledTransports: reverbUsesHttps ? ['wss'] : ['ws'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: csrfToken ? {
                'X-CSRF-TOKEN': csrfToken,
            } : {},
        },
    });
} else if (reverbKey && pageUsesHttps && !reverbUsesHttps) {
    console.warn('Echo disabled: HTTPS page requires REVERB_SCHEME=https or a secure WebSocket proxy.');
}
