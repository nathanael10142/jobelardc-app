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

// --- Références aux éléments du DOM pour les appels ---
const initiateCallModalElement = document.getElementById('initiateCallModal');
const contactSelect = document.getElementById('contactSelect');
const initiateCallForm = document.getElementById('initiateCallForm');

const incomingCallModal = new bootstrap.Modal(document.getElementById('incomingCallModal'));
const incomingCallerName = document.getElementById('incomingCallerName');
const incomingCallType = document.getElementById('incomingCallType');
const incomingCallerAvatar = document.getElementById('incomingCallerAvatar');
const acceptCallButton = document.getElementById('acceptCallButton');
const rejectCallButton = document.getElementById('rejectCallButton');

const activeCallModal = new bootstrap.Modal(document.getElementById('activeCallModal'));
const activeCallParticipantName = document.getElementById('activeCallParticipantName');
const callTimer = document.getElementById('callTimer');
const localVideo = document.getElementById('localVideo');
const remoteVideo = document.getElementById('remoteVideo');
const audioOnlyOverlay = document.getElementById('audioOnlyOverlay');
const audioOnlyParticipantName = document.getElementById('audioOnlyParticipantName');
const muteToggleButton = document.getElementById('muteToggleButton');
const videoToggleButton = document.getElementById('videoToggleButton');
const hangupButton = document.getElementById('hangupButton');

// --- Variables globales pour WebRTC ---
let localStream;
let peerConnection;
let currentCallId = null;
let currentCallerId = null; // L'ID de l'appelant (l'autre personne)
let currentReceiverId = null; // L'ID du destinataire (l'autre personne)
let currentCallType = null;
let callInterval; // Pour le timer d'appel
let callStartTime; // Pour calculer la durée

// Configuration ICE Servers (STUN/TURN) - Indispensable pour WebRTC
const iceServers = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        // { urls: 'turn:YOUR_TURN_SERVER_IP:3478', username: 'YOUR_USERNAME', credential: 'YOUR_PASSWORD' }
    ]
};

// --- Fonctions utilitaires pour l'UI ---
function showIncomingCallUI(callerName, callType, callerAvatarUrl, callId, callerId) {
    incomingCallerName.textContent = callerName;
    incomingCallType.textContent = `Appel ${callType}`;
    incomingCallerAvatar.src = callerAvatarUrl;
    currentCallId = callId;
    currentCallerId = callerId;
    currentCallType = callType;

    incomingCallModal.show();
    // Optionnel: Jouer une sonnerie d'appel
    // const ringtone = new Audio('/path/to/ringtone.mp3');
    // ringtone.loop = true;
    // ringtone.play();
}

function hideIncomingCallUI() {
    incomingCallModal.hide();
    // Optionnel: Arrêter la sonnerie
}

function showActiveCallUI(participantName, callType) {
    activeCallParticipantName.textContent = participantName;
    audioOnlyParticipantName.textContent = participantName; // Pour l'overlay audio
    activeCallModal.show();
    
    // Gérer l'affichage des vidéos/overlay audio
    if (callType === 'audio') {
        localVideo.style.display = 'none';
        remoteVideo.style.display = 'none';
        audioOnlyOverlay.style.display = 'flex';
        videoToggleButton.style.display = 'none'; // Cacher le bouton vidéo en audio seulement
    } else {
        localVideo.style.display = 'block';
        remoteVideo.style.display = 'block';
        audioOnlyOverlay.style.display = 'none';
        videoToggleButton.style.display = 'block';
    }

    // Démarrer le timer
    callStartTime = Date.now();
    callInterval = setInterval(updateCallTimer, 1000);
}

function hideActiveCallUI() {
    activeCallModal.hide();
    clearInterval(callInterval); // Arrêter le timer
    callTimer.textContent = '00:00'; // Réinitialiser le timer
    
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    localVideo.srcObject = null;
    remoteVideo.srcObject = null;
    
    currentCallId = null;
    currentCallerId = null;
    currentReceiverId = null;
    currentCallType = null;
}

function updateCallTimer() {
    const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
    const minutes = String(Math.floor(elapsed / 60)).padStart(2, '0');
    const seconds = String(elapsed % 60).padStart(2, '0');
    callTimer.textContent = `${minutes}:${seconds}`;
}

// --- Fonctions WebRTC ---

// 1. Obtenir le flux média local (caméra/micro)
async function getLocalMedia(callType) {
    try {
        const constraints = {
            audio: true,
            video: callType === 'video' ? { width: 640, height: 480 } : false
        };
        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        localVideo.srcObject = localStream;
        console.log('Local media stream obtained:', localStream);
        return localStream;
    } catch (error) {
        console.error('Error accessing local media:', error);
        alert('Impossible d\'accéder à votre caméra ou microphone. Veuillez vérifier les permissions.');
        return null;
    }
}

// 2. Créer la connexion RTCPeerConnection
function createPeerConnection(isCaller) {
    peerConnection = new RTCPeerConnection(iceServers);
    console.log('RTCPeerConnection created:', peerConnection);

    // Ajouter les pistes locales au peer connection
    if (localStream) {
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
        });
        console.log('Local tracks added to peer connection.');
    }

    // Gérer les ICE candidates (pour la connectivité réseau)
    peerConnection.onicecandidate = async (event) => {
        if (event.candidate) {
            console.log('Sending ICE candidate:', event.candidate);
            // Déterminer l'autre participant pour l'envoi du signal
            const otherParticipantId = (window.Laravel.user.id === currentCallerId) ? currentReceiverId : currentCallerId;
            await sendSignal('ice-candidate', event.candidate, otherParticipantId);
        }
    };

    // Gérer la réception des pistes distantes
    peerConnection.ontrack = (event) => {
        console.log('Remote track received:', event.streams[0]);
        if (remoteVideo.srcObject !== event.streams[0]) {
            remoteVideo.srcObject = event.streams[0];
        }
    };

    // Gérer l'état de la connexion
    peerConnection.onconnectionstatechange = () => {
        console.log('Peer connection state changed:', peerConnection.connectionState);
        if (peerConnection.connectionState === 'disconnected' || peerConnection.connectionState === 'failed') {
            console.warn('Peer connection disconnected or failed.');
            // Gérer la fin de l'appel ici si nécessaire
            // endCall(currentCallId, currentCallerId, Math.floor((Date.now() - callStartTime) / 1000));
        } else if (peerConnection.connectionState === 'connected') {
            console.log('Peer connection established!');
        }
    };
}

// 3. Envoyer des messages de signalisation via le backend
async function sendSignal(type, payload, receiverId) {
    try {
        // UTILISATION DE window.Laravel.routes.callsSignal
        const response = await fetch(window.Laravel.routes.callsSignal, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                call_id: currentCallId,
                receiver_id: receiverId, // L'autre participant
                type: type, // 'offer', 'answer', 'ice-candidate'
                payload: payload
            })
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(`Signal error: ${errorData.message || response.statusText}`);
        }
        console.log(`Signal '${type}' sent successfully.`);
    } catch (error) {
        console.error('Error sending signal:', error);
    }
}

// --- Logique d'appel (Appelant) ---
async function startCall(receiverId, callType) {
    currentReceiverId = receiverId; // Stocke l'ID du destinataire pour l'appelant
    currentCallType = callType;

    // 1. Obtenir les médias locaux
    const mediaStream = await getLocalMedia(callType);
    if (!mediaStream) return;

    // 2. Créer la connexion WebRTC
    createPeerConnection(true); // isCaller = true

    // 3. Créer l'offre SDP (Session Description Protocol)
    const offer = await peerConnection.createOffer();
    await peerConnection.setLocalDescription(offer);
    console.log('SDP Offer created and set as local description:', offer);

    // 4. Envoyer l'offre SDP au destinataire via le backend
    await sendSignal('offer', offer, receiverId);

    // 5. Afficher l'UI d'appel actif (en attente)
    // Le nom du participant sera celui que l'appelant a sélectionné
    const participantName = contactSelect.options[contactSelect.selectedIndex].textContent;
    showActiveCallUI(participantName, callType);
    // Au début, la vidéo distante sera noire en attendant la réponse
}

// --- Logique d'appel (Destinataire) ---
async function handleIncomingCall(data) {
    console.log('Handling incoming call:', data);
    const callerName = data.caller.name;
    const callerAvatarUrl = data.caller.profile_picture || `https://placehold.co/100x100/ccc/white?text=${data.caller.name.substring(0,2).toUpperCase()}`;
    
    currentCallId = data.call_id;
    currentCallerId = data.caller.id;
    currentCallType = data.call_type;

    showIncomingCallUI(callerName, data.call_type, callerAvatarUrl, data.call_id, data.caller.id);
}

async function acceptCall() {
    hideIncomingCallUI(); // Cacher le modal d'appel entrant

    // Envoyer l'acceptation au backend
    try {
        // UTILISATION DE window.Laravel.routes.callsAccept
        const response = await fetch(window.Laravel.routes.callsAccept, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                call_id: currentCallId,
                caller_id: currentCallerId,
            })
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(`Accept call error: ${errorData.message || response.statusText}`);
        }
        console.log('Call accepted response:', await response.json());

        // Obtenir les médias locaux
        const mediaStream = await getLocalMedia(currentCallType);
        if (!mediaStream) {
            await rejectCall(); // Ou une gestion d'erreur plus fine
            return;
        }

        // Créer la connexion WebRTC
        createPeerConnection(false); // isCaller = false

        // L'appelant enverra son offre SDP via l'événement .signal.
        // On attendra cet événement pour définir la description distante.
        // L'UI d'appel actif est affichée ici pour le receveur.
        showActiveCallUI(incomingCallerName.textContent, currentCallType);

    } catch (error) {
        console.error('Error accepting call:', error);
        alert('Erreur lors de l\'acceptation de l\'appel.');
        // En cas d'erreur, on peut aussi envoyer un rejet au caller
        endCall(currentCallId, currentCallerId, 0); // Marquer comme manqué ou rejeté
    }
}

async function rejectCall() {
    hideIncomingCallUI(); // Cacher le modal d'appel entrant
    try {
        // UTILISATION DE window.Laravel.routes.callsReject
        const response = await fetch(window.Laravel.routes.callsReject, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                call_id: currentCallId,
                caller_id: currentCallerId,
            })
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(`Reject call error: ${errorData.message || response.statusText}`);
        }
        console.log('Call rejected response:', await response.json());
    } catch (error) {
        console.error('Error rejecting call:', error);
        alert('Erreur lors du rejet de l\'appel.');
    } finally {
        hideActiveCallUI(); // Nettoyer l'UI d'appel actif si elle était visible
    }
}

async function endCall(callId, participantId, duration = 0) {
    hideActiveCallUI(); // Cache et nettoie l'UI d'appel actif
    hideIncomingCallUI(); // Cache l'UI d'appel entrant si elle était visible

    try {
        // UTILISATION DE window.Laravel.routes.callsEnd
        const response = await fetch(window.Laravel.routes.callsEnd, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                call_id: callId,
                participant_id: participantId, // L'ID de l'autre participant
                duration: duration // Durée en secondes
            })
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(`End call error: ${errorData.message || response.statusText}`);
        }
        console.log('Call ended response:', await response.json());
        // Recharger l'historique des appels après la fin de l'appel
        // location.reload(); // Peut être trop agressif, on peut aussi mettre à jour la liste dynamiquement
    } catch (error) {
        console.error('Error ending call:', error);
        alert('Erreur lors de la fin de l\'appel.');
    }
}

// --- Fonctions de contrôle d'appel ---
if (muteToggleButton) {
    muteToggleButton.addEventListener('click', () => {
        if (localStream) {
            localStream.getAudioTracks().forEach(track => {
                track.enabled = !track.enabled;
                muteToggleButton.querySelector('i').className = track.enabled ? 'fas fa-microphone' : 'fas fa-microphone-slash';
                console.log('Microphone toggled:', track.enabled);
            });
        }
    });
}

if (videoToggleButton) {
    videoToggleButton.addEventListener('click', () => {
        if (localStream) {
            localStream.getVideoTracks().forEach(track => {
                track.enabled = !track.enabled;
                videoToggleButton.querySelector('i').className = track.enabled ? 'fas fa-video' : 'fas fa-video-slash';
                localVideo.style.display = track.enabled ? 'block' : 'none';
                // Si la vidéo est désactivée, afficher l'overlay audio
                if (!track.enabled && currentCallType === 'video') {
                    audioOnlyOverlay.style.display = 'flex';
                } else if (track.enabled && currentCallType === 'video') {
                    audioOnlyOverlay.style.display = 'none';
                }
                console.log('Video toggled:', track.enabled);
            });
        }
    });
}

if (hangupButton) {
    hangupButton.addEventListener('click', () => {
        if (currentCallId && window.Laravel.user.id && (currentCallerId || currentReceiverId)) {
            // Déterminer l'ID de l'autre participant
            const otherParticipantId = (window.Laravel.user.id === currentCallerId) ? currentReceiverId : currentCallerId;
            const duration = Math.floor((Date.now() - callStartTime) / 1000);
            endCall(currentCallId, otherParticipantId, duration);
        } else {
            console.warn('Cannot hang up: call ID or participant ID missing. Forcing UI hide.');
            hideActiveCallUI(); // Forcer la fermeture de l'UI si les IDs sont manquants
        }
    });
}


// --- Événements DOM et Initialisation des listeners ---
document.addEventListener('DOMContentLoaded', function() {
    // Charger les utilisateurs lorsque le modal d'initiation est affiché
    if (initiateCallModalElement) {
        initiateCallModalElement.addEventListener('show.bs.modal', loadUsersForCall);
    }

    // Gérer la soumission du formulaire d'appel
    if (initiateCallForm) {
        initiateCallForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Empêche le rechargement de la page

            const receiverId = contactSelect.value;
            const callType = document.querySelector('input[name="call_type"]:checked').value;

            if (!receiverId) {
                alert('Veuillez sélectionner un contact.'); // Utiliser un modal personnalisé en production
                return;
            }

            console.log(`Initiating ${callType} call to user ID: ${receiverId}`);

            try {
                // UTILISATION DE window.Laravel.routes.callsInitiate
                const response = await fetch(window.Laravel.routes.callsInitiate, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        receiver_id: receiverId,
                        call_type: callType
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    alert(data.message + ` Call ID: ${data.call_id}`); // Utiliser un modal personnalisé
                    const modal = bootstrap.Modal.getInstance(initiateCallModalElement);
                    if (modal) modal.hide();
                    
                    currentCallId = data.call_id;
                    currentCallerId = data.caller.id; // L'appelant est l'utilisateur actuel
                    currentReceiverId = data.receiver.id; // Le destinataire est celui sélectionné
                    currentCallType = data.call_type;

                    // Démarrer l'appel WebRTC pour l'appelant
                    await startCall(data.receiver.id, data.call_type);

                } else {
                    alert('Erreur lors de l\'initiation de l\'appel: ' + (data.message || 'Erreur inconnue')); // Utiliser un modal personnalisé
                    console.error('Call initiation error:', data);
                }
            } catch (error) {
                console.error('Network or unexpected error during call initiation:', error);
                alert('Une erreur inattendue est survenue. Veuillez réessayer.'); // Utiliser un modal personnalisé
            }
        });
    }

    // Boutons d'acceptation/rejet d'appel entrant
    if (acceptCallButton) {
        acceptCallButton.addEventListener('click', acceptCall);
    }
    if (rejectCallButton) {
        rejectCallButton.addEventListener('click', rejectCall);
    }
});


// --- Écouteurs d'événements Laravel Echo pour les appels ---
// Ces écouteurs sont placés directement ici car Echo est initialisé au début de ce fichier.
// Ils ne dépendent pas du DOMContentLoaded car ils écoutent des événements réseau.
if (window.Laravel && window.Laravel.user && window.Laravel.user.id) {
    console.log(`Listening for call events on private-calls.${window.Laravel.user.id}`);
    window.Echo.private(`calls.${window.Laravel.user.id}`)
        .listen('.call-initiated', (e) => {
            console.log('Echo: Call initiated event received:', e);
            // Afficher l'UI d'appel entrant
            handleIncomingCall(e);
        })
        .listen('.call-accepted', async (e) => {
            console.log('Echo: Call accepted event received:', e);
            // Si l'utilisateur actuel est l'appelant et que l'appel a été accepté
            if (window.Laravel.user.id === e.caller.id && e.call_id === currentCallId) {
                alert(`${e.receiver.name} a accepté votre appel !`); // Utiliser un modal personnalisé
                // L'appelant attend juste la réception de l'Answer via le canal 'signal'.
                // Le showActiveCallUI a déjà été appelé par startCall.
            }
        })
        .listen('.call-rejected', (e) => {
            console.log('Echo: Call rejected event received:', e);
            if (e.call_id === currentCallId) {
                alert(`${e.receiver.name} a rejeté votre appel.`); // Utiliser un modal personnalisé
                hideActiveCallUI(); // Cacher l'UI d'appel actif pour l'appelant
                hideIncomingCallUI(); // Cacher l'UI d'appel entrant si c'est le cas
            }
        })
        .listen('.call-ended', (e) => {
            console.log('Echo: Call ended event received:', e);
            if (e.call_id === currentCallId) {
                alert(`Appel avec ${e.ender.name} terminé.`); // Utiliser un modal personnalisé
                hideActiveCallUI(); // Cacher l'UI d'appel actif pour les deux parties
                hideIncomingCallUI(); // Cacher l'UI d'appel entrant si c'est le cas
            }
        })
        .listen('.signal', async (e) => { // Écoute les messages de signalisation
            console.log('Echo: Signal event received:', e);
            if (e.call_id !== currentCallId) {
                console.warn('Received signal for unknown call ID. Ignoring.', e.call_id, currentCallId);
                return;
            }

            // Assurez-vous que le signal est destiné à l'utilisateur actuel
            if (e.receiver_id !== window.Laravel.user.id) {
                console.warn('Received signal not for current user. Ignoring.', e.receiver_id, window.Laravel.user.id);
                return;
            }

            // Si peerConnection n'est pas initialisé au moment de l'offre (cas du receveur)
            // C'est géré par l'appel à `acceptCall` qui crée le `peerConnection`.
            // Si le signal est une offre et que l'appel n'a pas encore été accepté
            // (donc pas de peerConnection), cette partie peut nécessiter une mise en file d'attente
            // ou une gestion plus fine. Pour l'instant, on suppose que `acceptCall` est déjà passé.
            if (!peerConnection) {
                console.error('PeerConnection not initialized when signal received. This should not happen for Offer/Answer/ICE.');
                return;
            }

            try {
                if (e.type === 'offer') {
                    // C'est le destinataire qui reçoit l'offre de l'appelant
                    if (peerConnection.signalingState !== 'stable') {
                        console.warn('PeerConnection is not stable, waiting for current signaling to complete before setting remote offer.');
                        // Vous pouvez mettre en file d'attente les offres ou les ignorer
                        // Pour une implémentation simple, on suppose stable.
                        return;
                    }
                    await peerConnection.setRemoteDescription(new RTCSessionDescription(e.payload));
                    console.log('SDP Offer set as remote description.');

                    // Créer la réponse (Answer)
                    const answer = await peerConnection.createAnswer();
                    await peerConnection.setLocalDescription(answer);
                    console.log('SDP Answer created and set as local description:', answer);

                    // Envoyer la réponse à l'appelant
                    await sendSignal('answer', answer, e.sender_id); // Envoyer à l'expéditeur de l'offre

                } else if (e.type === 'answer') {
                    // C'est l'appelant qui reçoit la réponse du destinataire
                    if (peerConnection.signalingState === 'have-local-offer') {
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(e.payload));
                        console.log('SDP Answer set as remote description.');
                    } else {
                        console.warn('Received SDP Answer in unexpected signaling state:', peerConnection.signalingState);
                    }
                } else if (e.type === 'ice-candidate') {
                    // Ajouter le candidat ICE reçu
                    await peerConnection.addIceCandidate(new RTCIceCandidate(e.payload));
                    console.log('ICE candidate added:', e.payload);
                }
            } catch (error) {
                console.error('Error handling signal event:', error);
            }
        })
        .error((error) => {
            console.error('Pusher channel error:', error);
        });
} else {
    console.warn('Laravel.user.id not found. Real-time call events will not be listened.');
}

// Fonction pour charger les utilisateurs dans le sélecteur de contact du modal
// Cette fonction est appelée par le DOMContentLoaded listener du modal
async function loadUsersForCall() {
    console.log('Loading users for call modal...');
    contactSelect.innerHTML = '<option value="">Chargement des contacts...</option>';

    try {
        // UTILISATION DE window.Laravel.routes.chatsSearchUsers
        // Assurez-vous que window.Laravel.routes.chatsSearchUsers est bien défini
        if (!window.Laravel || !window.Laravel.routes || !window.Laravel.routes.chatsSearchUsers) {
            console.error("window.Laravel.routes.chatsSearchUsers n'est pas défini. Vérifiez votre fichier Blade.");
            contactSelect.innerHTML = '<option value="">Erreur: Route non définie</option>';
            return;
        }
        const searchUsersUrl = window.Laravel.routes.chatsSearchUsers;
        console.log('Fetching users from:', searchUsersUrl);

        const response = await fetch(`${searchUsersUrl}?query=`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            const errorText = await response.text(); // Lire le texte de l'erreur pour plus de détails
            throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
        }

        const data = await response.json();
        const users = data.users; // Assurez-vous que votre API retourne les utilisateurs sous la clé 'users'
        console.log('Users received for call modal:', users);

        contactSelect.innerHTML = '<option value="">Sélectionnez un contact</option>';
        if (Array.isArray(users) && users.length > 0) {
            users.forEach(user => {
                if (window.Laravel && window.Laravel.user && user.id !== window.Laravel.user.id) { // Exclure l'utilisateur actuel
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name;
                    contactSelect.appendChild(option);
                }
            });
        } else {
            contactSelect.innerHTML = '<option value="">Aucun contact trouvé</option>';
        }

    } catch (error) {
        console.error('Error fetching users for call modal:', error);
        contactSelect.innerHTML = '<option value="">Erreur de chargement des contacts</option>';
    }
}
