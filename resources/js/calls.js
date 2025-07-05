// Importe Laravel Echo et Pusher JS
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Rend Pusher globalement disponible pour Echo
window.Pusher = Pusher;

// Initialise Laravel Echo
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY, // Récupère la clé depuis .env via Vite/Mix
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER, // Récupère le cluster
    forceTLS: true // Force la connexion sécurisée (HTTPS)
});

console.log('Laravel Echo initialized for Pusher.');

// --- LOGIQUE DE GESTION DES APPELS WEB RTC ---
// (Cette partie sera développée dans les prochaines étapes)

// Récupère l'ID de l'utilisateur connecté depuis l'objet global Laravel
// Cet objet est créé dans votre layout Blade (par exemple, resources/views/layouts/app.blade.php)
const currentUserId = window.Laravel.user.id;

if (currentUserId) {
    console.log(`Listening for calls on private-calls.${currentUserId} channel.`);

    // Écoute le canal privé pour l'utilisateur connecté
    window.Echo.private(`calls.${currentUserId}`)
        .listen('.call-initiated', (e) => { // .call-initiated correspond à broadcastAs() dans CallInitiated
            console.log('Call initiated event received:', e);
            alert(`Appel entrant de ${e.caller.name} (${e.call_type}) !`);
            // Ici, vous afficherez une interface d'appel entrant
            // et commencerez la logique WebRTC
        })
        .listen('.call-accepted', (e) => {
            console.log('Call accepted event received:', e);
            alert(`${e.receiver.name} a accepté votre appel !`);
            // Ici, vous démarrerez la connexion WebRTC pour l'appelant
        })
        .listen('.call-rejected', (e) => {
            console.log('Call rejected event received:', e);
            alert(`${e.receiver.name} a rejeté votre appel.`);
            // Gérer l'affichage de l'appel rejeté
        })
        .listen('.call-ended', (e) => {
            console.log('Call ended event received:', e);
            alert(`Appel avec ${e.ender.name} terminé.`);
            // Nettoyer l'interface d'appel
        })
        .error((error) => {
            console.error('Pusher channel error:', error);
        });
} else {
    console.warn('Current user ID not found. Cannot listen for private calls.');
}
