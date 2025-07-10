import './bootstrap'; // Ou tout autre fichier de démarrage nécessaire

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Rendre Pusher disponible globalement
window.Pusher = Pusher;

// Initialiser Laravel Echo et le rendre disponible globalement
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY, // Assurez-vous que cette variable est définie dans votre .env
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER, // Assurez-vous que cette variable est définie
    forceTLS: true,
    authEndpoint: '/broadcasting/auth', // Point d'authentification pour les canaux privés
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                axios.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name
                }, {
                    withCredentials: true // Important pour envoyer les cookies de session
                })
                .then(response => callback(null, response.data))
                .catch(error => {
                    console.error('Erreur d\'autorisation Broadcasting:', error);
                    callback(new Error('Échec de l\'authentification Broadcasting.'), null);
                });
            }
        };
    },
});

// Vous pouvez ajouter d'autres configurations globales ici si nécessaire
// Par exemple, pour Axios si ce n'est pas déjà fait ailleurs
import axios from 'axios';
axios.defaults.baseURL = 'http://127.0.0.1:8000'; // Ou votre URL de base
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// La définition de window.Laravel doit être dans le fichier Blade principal (app.blade.php ou layouts/user.blade.php)
// et non ici dans app.js.
