import _ from 'lodash';
window._ = _;

import 'bootstrap';

/**
 * We'll load the axios HTTP library which provides an easy API for communicating
 * with your backend HTTP services. This gives a simple, clean API to fetch
 * HTTP requests to and from your Laravel application.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time features.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Ensure that VITE_PUSHER_APP_KEY and VITE_PUSHER_APP_CLUSTER are correctly
// picked up by Vite from your .env file.
// If you see a truncated key in the WebSocket URL, it means Vite is not
// correctly embedding the full key. Re-running `npm run dev` after
// clearing caches is usually the fix.
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true, // Use HTTPS for WebSocket connection
    // authEndpoint: '/broadcasting/auth', // This is the default, usually not needed unless custom auth is used
    // auth: {
    //     headers: {
    //         Authorization: 'Bearer ' + localStorage.getItem('token'), // Example for API token auth
    //     },
    // },
});

// Example: Listen for a public event (if you have any)
// window.Echo.channel('public-channel-name')
//     .listen('SomePublicEvent', (e) => {
//         console.log('Public event received:', e);
//     });

// Example: Listen for a private channel authentication success (for debugging)
// window.Echo.connector.pusher.connection.bind('connected', () => {
//     console.log('Pusher connection established successfully!');
// });
// window.Echo.connector.pusher.connection.bind('disconnected', () => {
//     console.log('Pusher connection disconnected.');
// });
// window.Echo.connector.pusher.connection.bind('error', (err) => {
//     console.error('Pusher connection error:', err);
// });

