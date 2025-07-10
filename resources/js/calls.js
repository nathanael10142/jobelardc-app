import axios from 'axios';
import * as bootstrap from 'bootstrap'; // Import Bootstrap for modals
// Assurez-vous que Echo est correctement importé et configuré dans resources/js/bootstrap.js
// ou directement ici si vous n'utilisez pas app.js pour l'initialisation globale.
// Si vous utilisez window.Echo comme global, l'importation explicite n'est pas nécessaire ici,
// mais il faut s'assurer qu'il est disponible au moment de l'exécution.
// import Echo from 'laravel-echo';
// import Pusher from 'pusher-js';
// window.Pusher = Pusher;
// window.Echo = new Echo({ /* ... votre configuration Echo ... */ });

// ===============================================
// Variables Globales et État de l'Appel
// ===============================================
let peerConnection;
let localStream;
let remoteStream;
let currentCall = null;
let callTimerInterval;
let callStartTime;

// Récupérer l'ID de l'utilisateur connecté depuis l'attribut data-user-id du body
const currentLoggedInUserId = document.body.dataset.userId ? parseInt(document.body.dataset.userId, 10) : null;

// Configuration ICE Servers (utilisez des serveurs STUN/TURN réels en production)
const iceServers = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        // Ajoutez des serveurs TURN si nécessaire pour les réseaux complexes
        // { urls: 'turn:your-turn-server.com:3478', username: 'user', credential: 'password' },
    ]
};

// ===============================================
// Éléments du DOM
// ===============================================
const callsList = document.getElementById('callsList');
const callSearchInput = document.getElementById('callSearchInput');
const initiateCallForm = document.getElementById('initiateCallForm');
const contactSearchInput = document.getElementById('contactSearchInput');
const contactListForCall = document.getElementById('contactListForCall');
const selectedContactIdInput = document.getElementById('selectedContactId');
const startCallButton = document.getElementById('startCallButton');
const clearSearchButton = document.getElementById('clearSearchButton');

// Modals
const initiateCallModal = new bootstrap.Modal(document.getElementById('initiateCallModal'));
const incomingCallModal = new bootstrap.Modal(document.getElementById('incomingCallModal'), { backdrop: 'static', keyboard: false });
const activeCallModal = new bootstrap.Modal(document.getElementById('activeCallModal'), { backdrop: 'static', keyboard: false });

// Incoming Call Modal Elements
const incomingCallerName = document.getElementById('incomingCallerName');
const incomingCallerAvatar = document.getElementById('incomingCallerAvatar');
const incomingCallType = document.getElementById('incomingCallType');
const rejectCallButton = document.getElementById('rejectCallButton');
const acceptCallButton = document.getElementById('acceptCallButton');

// Active Call Modal Elements
const activeCallParticipantName = document.getElementById('activeCallParticipantName');
const callTimer = document.getElementById('callTimer');
const remoteVideo = document.getElementById('remoteVideo');
const localVideo = document.getElementById('localVideo');
const muteToggleButton = document.getElementById('muteToggleButton');
const videoToggleButton = document.getElementById('videoToggleButton');
const hangupButton = document.getElementById('hangupButton');
const audioOnlyOverlay = document.getElementById('audioOnlyOverlay');
const audioOnlyParticipantName = document.getElementById('audioOnlyParticipantName');


// ===============================================
// Fonctions utilitaires
// ===============================================

function showCustomAlert(message, type = 'info') {
    const alertDiv = document.getElementById('customAlert');
    const alertMessageSpan = document.getElementById('customAlertMessage');
    alertMessageSpan.textContent = message;
    alertDiv.className = `alert alert-${type} fixed-top text-center`; // Update class for styling
    alertDiv.style.display = 'block';
    setTimeout(() => {
        alertDiv.style.display = 'none';
    }, 5000); // Hide after 5 seconds
}

function formatDuration(seconds) {
    if (seconds === null || isNaN(seconds)) {
        return '';
    }
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.split(' ');
    if (parts.length > 1) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name[0].toUpperCase();
}

function resetCallState() {
    console.log('Réinitialisation de l\'état de l\'appel.');
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    if (remoteStream) {
        remoteStream.getTracks().forEach(track => track.stop());
        remoteStream = null;
    }
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    currentCall = null;
    clearInterval(callTimerInterval);
    callTimer.textContent = '00:00';
    localVideo.srcObject = null;
    remoteVideo.srcObject = null;
    remoteVideo.style.display = 'block'; // S'assurer que la vidéo distante est visible par défaut
    localVideo.style.display = 'block'; // S'assurer que la vidéo locale est visible par défaut
    audioOnlyOverlay.style.display = 'none'; // Cacher l'overlay audio
    muteToggleButton.querySelector('i').className = 'fas fa-microphone';
    videoToggleButton.querySelector('i').className = 'fas fa-video';
    startCallButton.disabled = true; // Désactiver le bouton d'appel au cas où
    selectedContactIdInput.value = ''; // Vider le contact sélectionné
    contactListForCall.innerHTML = '<p class="text-muted text-center p-2">Commencez à taper pour rechercher des contacts...</p>'; // Réinitialiser la liste
    document.querySelectorAll('#contactListForCall .list-group-item').forEach(item => item.classList.remove('active')); // Désélectionner tous les contacts
}


// ===============================================
// Fonctions d'affichage des modaux
// ===============================================

function showIncomingCallModal(call) {
    incomingCallerName.textContent = call.caller.name || 'Appelant Inconnu';
    incomingCallerAvatar.src = call.caller.avatar_url || 'https://placehold.co/100x100/ccc/white?text=?'; // Placeholder si pas d'avatar
    incomingCallType.textContent = call.call_type === 'audio' ? 'Appel Audio Entrant' : 'Appel Vidéo Entrant';

    // Afficher ou masquer les boutons d'appel/vidéo selon le type
    if (call.call_type === 'audio') {
        // Pour l'audio, masquer la vidéo locale et distante et afficher l'overlay
        remoteVideo.style.display = 'none';
        localVideo.style.display = 'none';
        audioOnlyOverlay.style.display = 'flex'; // Afficher l'overlay
        audioOnlyParticipantName.textContent = call.caller.name || 'Appelant Inconnu'; // Mettre à jour le nom
        videoToggleButton.style.display = 'none'; // Masquer le bouton vidéo en mode audio
    } else {
        remoteVideo.style.display = 'block';
        localVideo.style.display = 'block';
        audioOnlyOverlay.style.display = 'none';
        videoToggleButton.style.display = 'block'; // Afficher le bouton vidéo
    }


    incomingCallModal.show();
    // Jouer une sonnerie
    // Vous devrez avoir un élément audio dans votre HTML ou le créer dynamiquement
    // Ex: <audio id="ringingSound" src="/sounds/ringtone.mp3" loop></audio>
    const ringingSound = document.getElementById('ringingSound');
    if (ringingSound) {
        ringingSound.play().catch(e => console.error("Erreur de lecture de la sonnerie:", e));
    }
}

function hideIncomingCallModal() {
    incomingCallModal.hide();
    const ringingSound = document.getElementById('ringingSound');
    if (ringingSound) {
        ringingSound.pause();
        ringingSound.currentTime = 0;
    }
}

async function showCallModal(participantName, callType) {
    activeCallParticipantName.textContent = participantName;
    audioOnlyParticipantName.textContent = participantName; // Also update for audio overlay

    // Gérer l'affichage des vidéos/overlay en fonction du type d'appel
    if (callType === 'audio') {
        remoteVideo.style.display = 'none';
        localVideo.style.display = 'none';
        audioOnlyOverlay.style.display = 'flex'; // Afficher l'overlay pour l'audio
        videoToggleButton.style.display = 'none'; // Masquer le bouton vidéo
    } else {
        remoteVideo.style.display = 'display'; // 'block' or 'flex' depending on your CSS
        localVideo.style.display = 'display'; // 'block' or 'flex' depending on your CSS
        audioOnlyOverlay.style.display = 'none';
        videoToggleButton.style.display = 'block'; // Afficher le bouton vidéo
    }


    activeCallModal.show();
    startCallTimer();
}

function hideCallModal() {
    activeCallModal.hide();
    clearInterval(callTimerInterval);
    callTimer.textContent = '00:00';
}

// ===============================================
// Fonctions WebRTC
// ===============================================

async function initializePeerConnection(isCaller, callType) {
    resetCallState(); // S'assurer que tout est propre avant de commencer un nouvel appel
    console.log('Initialisation de PeerConnection...');
    peerConnection = new RTCPeerConnection(iceServers);

    // Gérer les flux locaux
    try {
        const constraints = callType === 'video' ? { video: true, audio: true } : { audio: true, video: false };
        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        localVideo.srcObject = localStream;
        localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));
        console.log('Flux local ajouté.');
    } catch (error) {
        console.error('Erreur lors de l\'accès aux médias locaux:', error);
        showCustomAlert('Impossible d\'accéder à votre caméra/micro. Veuillez vérifier les permissions.', 'danger');
        return false;
    }

    // Événement quand des pistes distantes sont reçues
    peerConnection.ontrack = (event) => {
        console.log('Piste distante reçue:', event.track.kind);
        if (remoteVideo.srcObject !== event.streams[0]) {
            remoteStream = event.streams[0];
            remoteVideo.srcObject = remoteStream;
            console.log('Flux distant défini sur la vidéo distante.');
        }
        // Gérer l'affichage de l'overlay audio si la vidéo est coupée
        if (callType === 'audio' || (remoteStream && remoteStream.getVideoTracks().length === 0)) {
            remoteVideo.style.display = 'none';
            localVideo.style.display = 'none';
            audioOnlyOverlay.style.display = 'flex';
        } else {
            remoteVideo.style.display = 'block';
            localVideo.style.display = 'block';
            audioOnlyOverlay.style.display = 'none';
        }
    };

    // Événement pour la collecte des candidats ICE
    peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
            console.log('Nouveau candidat ICE local:', event.candidate);
            // Envoyer le candidat ICE au serveur de signalisation
            sendSignalingMessage('ice-candidate', event.candidate);
        }
    };

    // Événement pour la connexion ICE (pour la détection de la connexion)
    peerConnection.oniceconnectionstatechange = () => {
        console.log('ICE connection state changed:', peerConnection.iceConnectionState);
        if (peerConnection.iceConnectionState === 'disconnected' || peerConnection.iceConnectionState === 'failed') {
            console.warn('ICE connection disconnected or failed. Ending call.');
            // En production, vous pourriez vouloir essayer de reconnecter ou montrer un message.
            if (activeCallModal._isShown) { // Only end if call is actively displayed
                showCustomAlert("La connexion à l'appel a été perdue.", "danger");
                endCall();
            }
        } else if (peerConnection.iceConnectionState === 'connected') {
            console.log('ICE connection established!');
            // Start timer when connected
            if (!callTimerInterval) {
                 startCallTimer();
            }
        }
    };

    // Créer offre/réponse et définir les descriptions locales
    if (isCaller) {
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        console.log('Offre locale définie.');
        return offer;
    } else {
        // La réponse sera créée après avoir défini l'offre distante
        return null;
    }
    return true; // Indique que l'initialisation a réussi jusqu'à présent
}


async function sendSignalingMessage(type, payload) {
    if (!currentCall || !currentCall.call_uuid) {
        console.error('Impossible d\'envoyer le message de signalisation : currentCall ou call_uuid est manquant.');
        return;
    }
    try {
        const signalUrl = route('api.calls.signal', { call_uuid: currentCall.call_uuid });
        await axios.post(signalUrl, {
            type: type,
            payload: payload,
            call_id: currentCall.id, // Assurez-vous que l'ID de l'appel est envoyé
            receiver_id: currentCall.caller_id === currentLoggedInUserId ? currentCall.receiver_id : currentCall.caller_id
        });
        console.log(`Message de signalisation "${type}" envoyé.`);
    } catch (error) {
        console.error('Erreur lors de l\'envoi du message de signalisation:', error);
        showCustomAlert('Erreur de signalisation WebRTC. Veuillez réessayer.', 'danger');
    }
}

function startCallTimer() {
    if (callTimerInterval) clearInterval(callTimerInterval); // Clear any existing timer
    callStartTime = Date.now();
    callTimerInterval = setInterval(() => {
        const elapsedSeconds = Math.floor((Date.now() - callStartTime) / 1000);
        callTimer.textContent = formatDuration(elapsedSeconds);
    }, 1000);
}

// ===============================================
// Actions d'appel
// ===============================================

async function initiateCall() {
    const receiverId = selectedContactIdInput.value;
    const callType = document.querySelector('input[name="call_type"]:checked').value;

    if (!receiverId || !currentLoggedInUserId) {
        showCustomAlert('Veuillez sélectionner un contact et assurez-vous d\'être connecté.', 'warning');
        return;
    }

    try {
        // Étape 1: Initialiser l'appel sur le serveur
        const response = await axios.post(route('api.calls.initiate'), {
            receiver_id: receiverId,
            call_type: callType,
            caller_id: currentLoggedInUserId // S'assurer que le caller_id est envoyé
        });
        currentCall = response.data.call; // Stocker les détails de l'appel depuis la réponse du serveur
        console.log('Appel initié sur le serveur:', currentCall);

        // Étape 2: Initialiser PeerConnection et créer l'offre SDP
        const offer = await initializePeerConnection(true, callType); // isCaller = true
        if (!offer) {
            throw new Error('Échec de l\'initialisation de PeerConnection.');
        }

        // Étape 3: Envoyer l'offre SDP au serveur de signalisation
        await sendSignalingMessage('offer', offer);
        showCustomAlert('Appel lancé...', 'info');
        initiateCallModal.hide(); // Fermer la modale d'initiation
        showCallModal(currentCall.receiver.name || 'Destinataire', callType); // Afficher la modale d'appel actif
    } catch (error) {
        console.error('Erreur lors de l\'initiation de l\'appel:', error);
        showCustomAlert('Erreur lors de l\'initiation de l\'appel. Veuillez réessayer.', 'danger');
        resetCallState();
    }
}


async function acceptCall() {
    if (!currentCall) {
        console.error('Aucun appel en cours à accepter.');
        showCustomAlert('Aucun appel en cours à accepter.', 'warning');
        return;
    }

    try {
        console.log('Acceptation de l\'appel:', currentCall);

        // Étape 1: Initialiser PeerConnection (en tant que récepteur)
        // Passer le type d'appel à initializePeerConnection pour obtenir les bonnes contraintes
        const offerPayload = currentCall.offer_payload; // Assurez-vous que l'offre est stockée dans currentCall
        if (!offerPayload) {
            throw new Error('Aucune offre SDP reçue pour cet appel.');
        }

        await initializePeerConnection(false, currentCall.call_type); // isCaller = false
        await peerConnection.setRemoteDescription(new RTCSessionDescription(offerPayload));
        console.log('Offre distante définie.');

        // Étape 2: Créer et envoyer la réponse SDP
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        console.log('Réponse locale définie.');

        // Mettre à jour l'état de l'appel sur le serveur comme 'accepté'
        const acceptUrl = route('api.calls.accept', { call_uuid: currentCall.call_uuid });
        await axios.post(acceptUrl, {
            answer_payload: answer,
            receiver_id: currentLoggedInUserId,
            caller_id: currentCall.caller_id
        });
        console.log('Appel accepté sur le serveur.');

        // Envoyer la réponse SDP au serveur de signalisation
        await sendSignalingMessage('answer', answer);
        console.log('Réponse SDP envoyée.');

        hideIncomingCallModal(); // Fermer la modale d'appel entrant
        showCallModal(currentCall.caller.name || 'Appelant', currentCall.call_type); // Afficher la modale d'appel actif
        showCustomAlert('Appel accepté !', 'success');

    } catch (error) {
        console.error('Erreur lors de l\'acceptation de l\'appel:', error);
        showCustomAlert('Erreur lors de l\'acceptation de l\'appel. Veuillez réessayer.', 'danger');
        hideIncomingCallModal(); // Fermer la modale d'appel entrant en cas d'échec
        resetCallState();
    }
}

async function rejectCall() {
    if (!currentCall) {
        console.error('Aucun appel en cours à rejeter.');
        return;
    }
    try {
        const rejectUrl = route('api.calls.reject', { call_uuid: currentCall.call_uuid });
        await axios.post(rejectUrl, {
            receiver_id: currentLoggedInUserId // L'ID du récepteur est requis pour la logique côté serveur
        });
        console.log('Appel rejeté sur le serveur.');
        showCustomAlert('Appel rejeté.', 'info');
    } catch (error) {
        console.error('Erreur lors du rejet de l\'appel:', error);
        showCustomAlert('Erreur lors du rejet de l\'appel. Veuillez réessayer.', 'danger');
    } finally {
        hideIncomingCallModal();
        resetCallState();
        fetchCallHistory(); // Rafraîchir l'historique après rejet
    }
}

async function endCall() {
    if (!currentCall) {
        console.warn('Aucun appel actif à terminer.');
        return;
    }

    try {
        const endUrl = route('api.calls.end', { call_uuid: currentCall.call_uuid });
        await axios.post(endUrl, {
            call_id: currentCall.id, // S'assurer d'envoyer l'ID de l'appel
            duration: Math.floor((Date.now() - callStartTime) / 1000) // Envoyer la durée en secondes
        });
        console.log('Appel terminé sur le serveur.');
        showCustomAlert('Appel terminé.', 'info');
    } catch (error) {
        console.error('Erreur lors de la fin de l\'appel:', error);
        showCustomAlert('Erreur lors de la fin de l\'appel. Veuillez réessayer.', 'danger');
    } finally {
        hideCallModal();
        resetCallState();
        fetchCallHistory(); // Rafraîchir l'historique après fin d'appel
    }
}


// ===============================================
// Fonctions de gestion de l'interface
// ===============================================

function toggleMute() {
    if (localStream) {
        localStream.getAudioTracks().forEach(track => {
            track.enabled = !track.enabled;
            muteToggleButton.querySelector('i').className = track.enabled ? 'fas fa-microphone' : 'fas fa-microphone-slash';
        });
    }
}

function toggleVideo() {
    if (localStream) {
        const videoTracks = localStream.getVideoTracks();
        if (videoTracks.length > 0) {
            videoTracks.forEach(track => {
                track.enabled = !track.enabled;
                videoToggleButton.querySelector('i').className = track.enabled ? 'fas fa-video' : 'fas fa-video-slash';
            });
            // Gérer l'affichage de l'overlay audio si la vidéo est coupée
            if (!videoTracks[0].enabled) {
                localVideo.style.display = 'none';
                remoteVideo.style.display = 'none'; // Could also hide remote video if local video is off
                audioOnlyOverlay.style.display = 'flex';
            } else {
                localVideo.style.display = 'block';
                remoteVideo.style.display = 'block';
                audioOnlyOverlay.style.display = 'none';
            }
        } else {
            // Si pas de piste vidéo au départ (appel audio), on peut essayer d'en ajouter une
            // Ceci est un scénario plus complexe (passer d'audio à vidéo)
            console.warn("Pas de piste vidéo locale à basculer. Tenter d'ajouter une piste vidéo n'est pas implémenté ici.");
            showCustomAlert("Fonctionnalité de passage en appel vidéo non disponible pour cet appel.", "warning");
        }
    }
}


// ===============================================
// Récupération et affichage de l'historique des appels
// ===============================================

async function fetchCallHistory(searchQuery = '') {
    callsList.innerHTML = `
        <div class="alert alert-info text-center whatsapp-card" role="alert">
            <i class="fas fa-spinner fa-spin me-2"></i> Chargement de l'historique des appels...
        </div>
    `;
    try {
        const url = route('api.calls.history', { search: searchQuery });
        const response = await axios.get(url);
        const calls = response.data;
        renderCallHistory(calls);
    } catch (error) {
        console.error('Erreur lors de la récupération de l\'historique des appels:', error);
        callsList.innerHTML = `
            <div class="alert alert-danger text-center whatsapp-card" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> Erreur lors du chargement de l'historique.
            </div>
        `;
        showCustomAlert('Erreur lors du chargement de l\'historique des appels.', 'danger');
    }
}

function renderCallHistory(calls) {
    if (calls.length === 0) {
        callsList.innerHTML = `
            <div class="alert alert-info text-center whatsapp-card" role="alert">
                <i class="fas fa-info-circle me-2"></i> Aucun appel trouvé.
            </div>
        `;
        return;
    }

    callsList.innerHTML = ''; // Clear previous content

    const callTypeIcons = {
        'audio': 'fas fa-phone',
        'video': 'fas fa-video',
    };

    const callStatusDisplay = {
        'initiated': {
            'icon': 'fas fa-hourglass-half text-info',
            'text_caller': 'Appel en attente...',
            'text_receiver': 'Appel entrant',
        },
        'accepted': {
            'icon': 'fas fa-check-circle text-success',
            'text': 'Appel terminé',
        },
        'rejected': {
            'icon': 'fas fa-times-circle text-danger',
            'text_caller': 'Appel rejeté',
            'text_receiver': 'Appel rejeté',
        },
        'missed': {
            'icon': 'fas fa-phone-slash text-danger',
            'text_caller': 'Appel non répondu',
            'text_receiver': 'Appel manqué',
        },
        'ended': {
            'icon': 'fas fa-phone-alt text-muted',
            'text': 'Appel terminé',
        },
        'cancelled': {
            'icon': 'fas fa-ban text-secondary',
            'text': 'Appel annulé',
        },
    };

    calls.forEach(call => {
        const isCaller = call.caller_id === currentLoggedInUserId;
        const participant = isCaller ? call.receiver : call.caller;
        const participantName = participant ? (participant.name || 'Inconnu') : 'Inconnu';
        const participantAvatar = participant ? (participant.avatar_url || null) : null;
        const callTypeIcon = callTypeIcons[call.call_type] || 'fas fa-phone';

        let statusText = '';
        let statusIconClass = '';
        let callDirectionIcon = ''; // Icon for incoming/outgoing

        if (isCaller) {
            callDirectionIcon = '<i class="fas fa-arrow-up text-success me-1"></i>'; // Appel sortant
            statusText = callStatusDisplay[call.status]?.text_caller || callStatusDisplay[call.status]?.text || call.status;
            statusIconClass = callStatusDisplay[call.status]?.icon || 'fas fa-question-circle text-warning';
            if (call.status === 'missed') {
                callDirectionIcon = '<i class="fas fa-arrow-up text-warning me-1"></i>'; // Non répondu par le destinataire
            }
        } else { // Is Receiver
            callDirectionIcon = '<i class="fas fa-arrow-down text-info me-1"></i>'; // Appel entrant
            statusText = callStatusDisplay[call.status]?.text_receiver || callStatusDisplay[call.status]?.text || call.status;
            statusIconClass = callStatusDisplay[call.status]?.icon || 'fas fa-question-circle text-warning';
            if (call.status === 'missed') {
                callDirectionIcon = '<i class="fas fa-arrow-down text-danger me-1"></i>'; // Manqué
            }
        }

        // Override statusText for accepted/ended calls to include duration
        if (call.status === 'accepted' || call.status === 'ended') {
            statusText = `${callStatusDisplay[call.status]?.text} (${formatDuration(call.duration)})`;
        }


        const callTime = new Date(call.created_at).toLocaleString('fr-FR', {
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const callCard = `
            <div class="card call-card mb-3 shadow-sm">
                <div class="card-body p-3 d-flex align-items-center">
                    ${participantAvatar ?
                        `<img src="${participantAvatar}" alt="${participantName}" class="avatar-thumbnail me-3">` :
                        `<div class="avatar-text-placeholder bg-secondary me-3">${getInitials(participantName)}</div>`
                    }
                    <div class="flex-grow-1 overflow-hidden">
                        <h6 class="profile-name mb-0">${participantName}</h6>
                        <p class="call-info text-muted mb-1">
                            ${callDirectionIcon}
                            <span class="${statusIconClass} me-1"></span> ${statusText}
                        </p>
                    </div>
                    <div class="ms-auto text-end flex-shrink-0">
                        <small class="call-time d-block">${callTime}</small>
                        <button class="btn btn-sm btn-outline-whatsapp-green mt-2 replay-call-btn"
                                data-contact-id="${participant.id}"
                                data-call-type="${call.call_type}"
                                data-contact-name="${participantName}"
                                title="Appeler ${participantName}">
                            <i class="${callTypeIcon}"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        callsList.insertAdjacentHTML('beforeend', callCard);
    });

    // Add event listeners to replay buttons
    document.querySelectorAll('.replay-call-btn').forEach(button => {
        button.addEventListener('click', function() {
            const contactId = this.dataset.contactId;
            const callType = this.dataset.callType;
            const contactName = this.dataset.contactName;

            // Pre-select contact in the modal and open it
            // This is a simplified approach. In a real app, you might fetch contact details.
            selectedContactIdInput.value = contactId;
            contactSearchInput.value = contactName; // Fill search input with name
            startCallButton.disabled = false; // Enable call button

            // Visually select the contact (if it exists in the list)
            document.querySelectorAll('#contactListForCall .list-group-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.contactId === contactId) {
                    item.classList.add('active');
                }
            });

            // Set the radio button for call type
            document.querySelector(`input[name="call_type"][value="${callType}"]`).checked = true;

            initiateCallModal.show();
        });
    });
}


// ===============================================
// Fonctions de recherche de contact pour nouvel appel
// ===============================================

const fetchUsersForCall = debounce(async (query) => {
    if (query.length < 2) { // Minimum 2 caractères pour rechercher
        contactListForCall.innerHTML = '<p class="text-muted text-center p-2">Commencez à taper pour rechercher des contacts...</p>';
        startCallButton.disabled = true;
        selectedContactIdInput.value = '';
        return;
    }

    try {
        const searchUrl = route('api.users.search', { query: query });
        const response = await axios.get(searchUrl);
        const users = response.data;
        renderContactList(users);
    } catch (error) {
        console.error('Erreur lors de la recherche de contacts:', error);
        contactListForCall.innerHTML = '<p class="text-danger text-center p-2">Erreur de chargement des contacts.</p>';
    }
}, 300); // Debounce de 300ms


function renderContactList(users) {
    contactListForCall.innerHTML = '';
    if (users.length === 0) {
        contactListForCall.innerHTML = '<p class="text-muted text-center p-2">Aucun contact trouvé.</p>';
        return;
    }

    users.forEach(user => {
        if (user.id === currentLoggedInUserId) {
            return; // Ne pas afficher l'utilisateur actuel dans la liste des contacts pour l'appel
        }

        const avatarHtml = user.avatar_url
            ? `<img src="${user.avatar_url}" alt="${user.name}" class="avatar-thumbnail me-3">`
            : `<div class="avatar-text-placeholder bg-primary me-3">${getInitials(user.name)}</div>`;

        const listItem = document.createElement('a');
        listItem.href = "#";
        listItem.className = 'list-group-item list-group-item-action d-flex align-items-center';
        listItem.dataset.contactId = user.id;
        listItem.innerHTML = `
            ${avatarHtml}
            <span class="user-name">${user.name}</span>
        `;
        listItem.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('#contactListForCall .list-group-item').forEach(item => item.classList.remove('active'));
            listItem.classList.add('active');
            selectedContactIdInput.value = user.id;
            startCallButton.disabled = false; // Activer le bouton "Démarrer l'Appel"
            contactSearchInput.value = user.name; // Remplir l'input de recherche avec le nom du contact sélectionné
            contactListForCall.innerHTML = ''; // Cacher la liste de recherche après sélection
        });
        contactListForCall.appendChild(listItem);
    });
}


// ===============================================
// Écouteurs d'événements
// ===============================================

document.addEventListener('DOMContentLoaded', () => {
    // Initial fetch of call history
    fetchCallHistory();

    // Search bar for calls
    callSearchInput.addEventListener('input', debounce((e) => {
        fetchCallHistory(e.target.value);
    }, 300)); // Debounce search input

    // Initiate Call Form submission
    initiateCallForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await initiateCall();
    });

    // Contact search input in modal
    contactSearchInput.addEventListener('input', (e) => {
        fetchUsersForCall(e.target.value);
    });

    // Clear search button in modal
    clearSearchButton.addEventListener('click', () => {
        contactSearchInput.value = '';
        contactListForCall.innerHTML = '<p class="text-muted text-center p-2">Commencez à taper pour rechercher des contacts...</p>';
        selectedContactIdInput.value = '';
        startCallButton.disabled = true;
    });

    // Incoming Call Modal buttons
    rejectCallButton.addEventListener('click', rejectCall);
    acceptCallButton.addEventListener('click', acceptCall);

    // Active Call Modal buttons
    muteToggleButton.addEventListener('click', toggleMute);
    videoToggleButton.addEventListener('click', toggleVideo);
    hangupButton.addEventListener('click', endCall);

    // Event listeners for modals being hidden to reset state
    document.getElementById('initiateCallModal').addEventListener('hidden.bs.modal', () => {
        // Reset contact search when modal is closed
        contactSearchInput.value = '';
        contactListForCall.innerHTML = '<p class="text-muted text-center p-2">Commencez à taper pour rechercher des contacts...</p>';
        selectedContactIdInput.value = '';
        startCallButton.disabled = true;
        // Ensure radio buttons reset to audio by default, or keep last selection
        document.getElementById('audioCall').checked = true;
    });

    document.getElementById('incomingCallModal').addEventListener('hidden.bs.modal', () => {
        // This modal can be hidden by reject/accept, but also if dismissed externally (shouldn't happen with static backdrop)
        // If it's hidden without a specific action, it implies a missed call or cancellation.
        // The Echo listeners should handle these states.
        // For safety, ensure state is reset if hidden without action
        if (!currentCall || (currentCall.status !== 'accepted' && currentCall.status !== 'ended')) {
             resetCallState();
        }
    });

    document.getElementById('activeCallModal').addEventListener('hidden.bs.modal', () => {
        // This modal should typically only be hidden after an 'endCall' action.
        // If hidden by other means (e.g., Bootstrap bug), ensure state is reset.
        resetCallState();
    });


    // ===============================================
    // Laravel Echo / WebSocket
    // ===============================================

    if (window.Echo && currentLoggedInUserId) {
        console.log(`Listening for events on private channel users.${currentLoggedInUserId}`);
        window.Echo.private(`users.${currentLoggedInUserId}`)
            .listen('CallInitiated', (e) => {
                console.log('Incoming CallInitiated event:', e);
                // Ensure the call is for the current user and not self-initiated (loopback)
                if (e.call.receiver_id == currentLoggedInUserId && e.call.caller_id != currentLoggedInUserId) {
                    currentCall = e.call; // Store the incoming call details
                    showIncomingCallModal(e.call);
                    showCustomAlert('Nouvel appel entrant !', 'info');
                }
                fetchCallHistory(); // Always refresh history on call events
            })
            .listen('CallAccepted', (e) => {
                console.log('CallAccepted event:', e);
                const callerIdFromEvent = parseInt(e.call.caller_id, 10);
                const receiverIdFromEvent = parseInt(e.call.receiver_id, 10);

                if (callerIdFromEvent === currentLoggedInUserId) { // If I am the caller and my call was accepted
                    hideIncomingCallModal(); // Ensure it's hidden, though it shouldn't be showing for caller
                    if (peerConnection && e.call.answer_payload) {
                        const answer = new RTCSessionDescription(e.call.answer_payload);
                        peerConnection.setRemoteDescription(answer).catch(err => console.error('Erreur setRemoteDescription (answer):', err));
                    }
                    currentCall = e.call; // Update currentCall with accepted state and potentially answer payload
                    showCallModal(e.call.receiver.name || 'Destinataire', e.call.call_type); // Show active call modal for caller
                    showCustomAlert('Appel accepté par le destinataire !', 'success');
                } else if (receiverIdFromEvent === currentLoggedInUserId) {
                    // Logic for receiver upon acceptance is handled by acceptCall function itself
                    // This listener ensures consistency if state somehow diverges
                    // We might not need explicit action here if `acceptCall` already manages it.
                }
                fetchCallHistory();
            })
            .listen('CallRejected', (e) => {
                console.log('CallRejected event:', e);
                const eventCallerId = parseInt(e.call.caller_id, 10);
                const eventReceiverId = parseInt(e.call.receiver_id, 10);

                if (eventCallerId === currentLoggedInUserId) { // If I am the caller and my call was rejected
                    showCustomAlert(`${e.call.receiver.name || 'Le destinataire'} a rejeté votre appel.`, 'info');
                    hideCallModal(); // Hide active call modal if it was showing
                } else if (eventReceiverId === currentLoggedInUserId) { // If I am the receiver and I received a rejection (e.g., from other device)
                    // This case is typically handled by `rejectCall` locally.
                    // But if another device rejects, ensure modal is hidden.
                }
                hideIncomingCallModal(); // Ensure incoming modal is hidden
                resetCallState(); // Reset WebRTC state
                fetchCallHistory(); // Refresh history
            })
            .listen('CallEnded', (e) => {
                console.log('CallEnded event:', e);
                const eventCallerId = parseInt(e.call.caller_id, 10);
                const eventReceiverId = parseInt(e.call.receiver_id, 10);
                const participantName = eventCallerId === currentLoggedInUserId ? e.call.receiver.name : e.call.caller.name;

                showCustomAlert(`L'appel avec ${participantName} est terminé.`, 'info');
                hideCallModal();
                hideIncomingCallModal();
                resetCallState();
                fetchCallHistory();
            })
            .listen('CallMissed', (e) => {
                console.log('CallMissed event:', e);
                const eventCallerId = parseInt(e.call.caller_id, 10);
                const eventReceiverId = parseInt(e.call.receiver_id, 10);
                const participantName = eventCallerId === currentLoggedInUserId ? e.call.receiver.name : e.call.caller.name;

                if (eventReceiverId === currentLoggedInUserId) { // If I am the receiver and missed a call
                    showCustomAlert(`Appel manqué de ${participantName}.`, 'warning');
                } else if (eventCallerId === currentLoggedInUserId) { // If I am the caller and my call was missed
                    showCustomAlert(`Votre appel à ${participantName} n'a pas été répondu.`, 'warning');
                }
                hideIncomingCallModal();
                resetCallState();
                fetchCallHistory();
            })
            .listen('SignalingMessage', async (e) => {
                console.log('SignalingMessage event:', e);
                try {
                    // It's crucial that currentCall is correctly set based on the CallInitiated event
                    // and that the signal corresponds to the currently active call UUID.
                    if (!currentCall || e.signal.call_id !== currentCall.id) {
                        console.warn('Signaling message for a non-current or unknown call. Ignoring.', e.signal.call_id, currentCall);
                        return;
                    }

                    if (e.signal.type === 'offer') {
                        // This 'offer' event listener primarily handles the case where the receiver
                        // receives the offer after the CallInitiated event.
                        if (parseInt(e.signal.receiver_id, 10) === currentLoggedInUserId) {
                            // Update the currentCall object with the offer payload.
                            // This ensures `acceptCall` can access it.
                            currentCall.offer_payload = e.signal.payload;
                            console.log('Offer payload received and stored in currentCall.');
                        }
                    } else if (e.signal.type === 'answer') {
                        // This listener is primarily for the caller to receive the answer.
                        if (parseInt(e.signal.receiver_id, 10) === currentLoggedInUserId && peerConnection) {
                             // The caller receives the answer from the receiver (my peerConnection's remoteDescription)
                             // and then sets it as the remote description.
                             // This 'receiver_id' in signal message refers to the original receiver of the call, not the receiver of the signal message.
                             // So the condition needs to check if the current user (caller) is the one expecting this answer.
                            if (currentCall.caller_id === currentLoggedInUserId) { // If I am the caller
                                await peerConnection.setRemoteDescription(new RTCSessionDescription(e.signal.payload));
                                console.log('Remote description (answer) set for caller.');
                            }
                        }
                    } else if (e.signal.type === 'ice-candidate') {
                        if (peerConnection && peerConnection.remoteDescription) {
                            await peerConnection.addIceCandidate(new RTCIceCandidate(e.signal.payload));
                            console.log('ICE candidate added.');
                        } else {
                            console.warn('Cannot add ICE candidate: peerConnection or remoteDescription not ready.');
                        }
                    }
                } catch (error) {
                    console.error('Erreur lors du traitement du signal WebRTC:', error);
                    showCustomAlert('Une erreur de communication est survenue pendant l\'appel.', 'danger');
                }
            });
    } else {
        console.warn('Laravel Echo (window.Echo) is not defined or userId is missing. Real-time features will not work.');
        showCustomAlert('Les fonctionnalités en temps réel ne sont pas disponibles. Veuillez vérifier la configuration de Laravel Echo et que vous êtes connecté.', 'warning');
    }
});

function debounce(func, delay) {
    let timeout;
    return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), delay);
    };
}