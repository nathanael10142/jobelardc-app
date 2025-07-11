import axios from "/node_modules/.vite/deps/axios.js?v=79e503dc";
import * as bootstrap from "/node_modules/.vite/deps/bootstrap.js?v=79e503dc";

let peerConnection;
let localStream;
let remoteStream;
let currentCall = null;
let callTimerInterval;
let callStartTime;

const currentLoggedInUserId = document.body.dataset.userId ? parseInt(document.body.dataset.userId, 10) : null;

const iceServers = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
    ]
};

const callsList = document.getElementById('callsList');
const callSearchInput = document.getElementById('callSearchInput');
const initiateCallForm = document.getElementById('initiateCallForm');
const contactSearchInput = document.getElementById('contactSearchInput');
const contactListForCall = document.getElementById('contactListForCall');
const selectedContactIdInput = document.getElementById('selectedContactId');
const startCallButton = document.getElementById('startCallButton');
const clearSearchButton = document.getElementById('clearSearchButton');

const initiateCallModal = new bootstrap.Modal(document.getElementById('initiateCallModal'));
const incomingCallModal = new bootstrap.Modal(document.getElementById('incomingCallModal'), { backdrop: 'static', keyboard: false });
const activeCallModal = new bootstrap.Modal(document.getElementById('activeCallModal'), { backdrop: 'static', keyboard: false });

const incomingCallerName = document.getElementById('incomingCallerName');
const incomingCallerAvatar = document.getElementById('incomingCallerAvatar');
const incomingCallType = document.getElementById('incomingCallType');
const rejectCallButton = document.getElementById('rejectCallButton');
const acceptCallButton = document.getElementById('acceptCallButton');

const activeCallParticipantName = document.getElementById('activeCallParticipantName');
const activeCallStatusText = document.getElementById('activeCallStatusText');
const callTimer = document.getElementById('callTimer');
const remoteVideo = document.getElementById('remoteVideo');
const localVideo = document.getElementById('localVideo');
const muteToggleButton = document.getElementById('muteToggleButton');
const videoToggleButton = document.getElementById('videoToggleButton');
const hangupButton = document.getElementById('hangupButton');
const audioOnlyOverlay = document.getElementById('audioOnlyOverlay');
const audioOnlyParticipantName = document.getElementById('audioOnlyParticipantName');


function showCustomAlert(message, type = 'info') {
    const alertDiv = document.getElementById('customAlert');
    const alertMessageSpan = document.getElementById('customAlertMessage');
    alertMessageSpan.textContent = message;
    alertDiv.className = `alert alert-${type} fixed-top text-center`;
    alertDiv.style.display = 'block';
    setTimeout(() => {
        alertDiv.style.display = 'none';
    }, 5000);
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
    remoteVideo.style.display = 'block';
    localVideo.style.display = 'block';
    audioOnlyOverlay.style.display = 'none';
    muteToggleButton.querySelector('i').className = 'fas fa-microphone';
    videoToggleButton.querySelector('i').className = 'fas fa-video';
    startCallButton.disabled = true;
    selectedContactIdInput.value = '';
    contactListForCall.innerHTML = '<p class="text-muted text-center p-2">Commencez à taper pour rechercher des contacts...</p>';
    document.querySelectorAll('#contactListForCall .list-group-item').forEach(item => item.classList.remove('active'));
    activeCallStatusText.textContent = '';
}


function showIncomingCallModal(call) {
    const callerName = call.caller ? (call.caller.name || 'Appelant Inconnu') : 'Appelant Inconnu';
    const callerAvatarUrl = call.caller ? (call.caller.profile_picture || 'https://placehold.co/100x100/ccc/white?text=?') : 'https://placehold.co/100x100/ccc/white?text=?';

    incomingCallerName.textContent = callerName;
    incomingCallerAvatar.src = callerAvatarUrl;
    incomingCallType.textContent = call.call_type === 'audio' ? 'Appel Audio Entrant' : 'Appel Vidéo Entrant';

    if (call.call_type === 'audio') {
        remoteVideo.style.display = 'none';
        localVideo.style.display = 'none';
        audioOnlyOverlay.style.display = 'flex';
        audioOnlyParticipantName.textContent = callerName;
        videoToggleButton.style.display = 'none';
    } else {
        remoteVideo.style.display = 'block';
        localVideo.style.display = 'block';
        audioOnlyOverlay.style.display = 'none';
        videoToggleButton.style.display = 'block';
    }

    incomingCallModal.show();
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

async function showCallModal(participantName, callType, status = 'En attente') {
    activeCallParticipantName.textContent = participantName;
    audioOnlyParticipantName.textContent = participantName;
    activeCallStatusText.textContent = status;

    if (callType === 'audio') {
        remoteVideo.style.display = 'none';
        localVideo.style.display = 'none';
        audioOnlyOverlay.style.display = 'flex';
        videoToggleButton.style.display = 'none';
    } else {
        remoteVideo.style.display = 'block';
        localVideo.style.display = 'block';
        audioOnlyOverlay.style.display = 'none';
        videoToggleButton.style.display = 'block';
    }

    activeCallModal.show();
}

function hideCallModal() {
    activeCallModal.hide();
    clearInterval(callTimerInterval);
    callTimer.textContent = '00:00';
}

async function initializePeerConnection(isCaller, callType) {
    console.log('Initialisation de PeerConnection...');
    peerConnection = new RTCPeerConnection(iceServers);

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

    peerConnection.ontrack = (event) => {
        console.log('Piste distante reçue:', event.track.kind);
        if (remoteVideo.srcObject !== event.streams[0]) {
            remoteStream = event.streams[0];
            remoteVideo.srcObject = remoteStream;
            console.log('Flux distant défini sur la vidéo distante.');
        }
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

    peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
            console.log('Nouveau candidat ICE local:', event.candidate);
            sendSignalingMessage('ice-candidate', event.candidate);
        }
    };

    peerConnection.oniceconnectionstatechange = () => {
        console.log('ICE connection state changed:', peerConnection.iceConnectionState);
        if (peerConnection.iceConnectionState === 'disconnected' || peerConnection.iceConnectionState === 'failed') {
            console.warn('ICE connection disconnected or failed. Ending call.');
            if (activeCallModal._isShown) {
                showCustomAlert("La connexion à l'appel a été perdue.", "danger");
                endCall(); // Déclenche la fin de l'appel pour les deux côtés
            }
        }
    };

    if (isCaller) {
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        console.log('Offre locale définie.');
        return offer;
    } else {
        return null;
    }
    return true;
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
            call_id: currentCall.id,
            receiver_id: currentCall.caller_id === currentLoggedInUserId ? currentCall.receiver_id : currentCall.caller_id
        });
        console.log(`Message de signalisation "${type}" envoyé.`);
    } catch (error) {
        console.error('Erreur lors de l\'envoi du message de signalisation:', error);
        showCustomAlert('Erreur de signalisation WebRTC. Veuillez réessayer.', 'danger');
    }
}

function startCallTimer() {
    if (callTimerInterval) clearInterval(callTimerInterval);
    callStartTime = Date.now();
    callTimerInterval = setInterval(() => {
        const elapsedSeconds = Math.floor((Date.now() - callStartTime) / 1000);
        callTimer.textContent = formatDuration(elapsedSeconds);
    }, 1000);
}

async function initiateCall() {
    const receiverId = selectedContactIdInput.value;
    const callType = document.querySelector('input[name="call_type"]:checked').value;

    if (!receiverId || !currentLoggedInUserId) {
        showCustomAlert('Veuillez sélectionner un contact et assurez-vous d\'être connecté.', 'warning');
        return;
    }

    try {
        const response = await axios.post(route('api.calls.initiate'), {
            receiver_id: receiverId,
            call_type: callType,
            caller_id: currentLoggedInUserId
        });
        currentCall = response.data.call;
        console.log('Appel initié sur le serveur:', currentCall);

        const offer = await initializePeerConnection(true, callType);
        if (!offer) {
            throw new Error('Échec de l\'initialisation de PeerConnection.');
        }

        await sendSignalingMessage('offer', offer);
        showCustomAlert('Appel lancé...', 'info');
        initiateCallModal.hide();
        showCallModal(contactSearchInput.value || 'Destinataire', callType, 'En attente');
    } catch (error) {
        console.error('Erreur lors de l\'initiation de l\'appel:', error);
        if (error.response && error.response.data) {
            console.error('Message d\'erreur du serveur:', error.response.data);
        }
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

        const offerPayload = currentCall.offer_payload;
        if (!offerPayload) {
            throw new Error('Aucune offre SDP reçue pour cet appel.');
        }

        await initializePeerConnection(false, currentCall.call_type);
        await peerConnection.setRemoteDescription(new RTCSessionDescription(offerPayload));
        console.log('Offre distante définie.');

        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        console.log('Réponse locale définie.');

        const acceptUrl = route('api.calls.accept', { call_uuid: currentCall.call_uuid });
        await axios.post(acceptUrl, {
            answer_payload: answer,
            receiver_id: currentLoggedInUserId,
            caller_id: currentCall.caller_id
        });
        console.log('Appel accepté sur le serveur.');

        await sendSignalingMessage('answer', answer);
        console.log('Réponse SDP envoyée.');

        hideIncomingCallModal();
        const participantName = currentCall.caller ? (currentCall.caller.name || 'Appelant') : 'Appelant';
        showCallModal(participantName, currentCall.call_type, 'Appel en cours');
        startCallTimer();
        showCustomAlert('Appel accepté !', 'success');

    } catch (error) {
        console.error('Erreur lors de l\'acceptation de l\'appel:', error);
        showCustomAlert('Erreur lors de l\'acceptation de l\'appel. Veuillez réessayer.', 'danger');
        hideIncomingCallModal();
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
            receiver_id: currentLoggedInUserId
        });
        console.log('Appel rejeté sur le serveur.');
        showCustomAlert('Appel rejeté.', 'info');
    } catch (error) {
        console.error('Erreur lors du rejet de l\'appel:', error);
        showCustomAlert('Erreur lors du rejet de l\'appel. Veuillez réessayer.', 'danger');
    } finally {
        hideIncomingCallModal();
        hideCallModal(); // S'assurer que la modale d'appel actif est aussi cachée si elle était ouverte
        resetCallState();
        fetchCallHistory();
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
            call_id: currentCall.id,
            duration: Math.floor((Date.now() - callStartTime) / 1000)
        });
        console.log('Appel terminé sur le serveur.');
        showCustomAlert('Appel terminé.', 'info');
    } catch (error) {
        console.error('Erreur lors de la fin de l\'appel:', error);
        showCustomAlert('Erreur lors de la fin de l\'appel. Veuillez réessayer.', 'danger');
    } finally {
        hideCallModal();
        resetCallState();
        fetchCallHistory();
    }
}


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
            if (!videoTracks[0].enabled) {
                localVideo.style.display = 'none';
                remoteVideo.style.display = 'none';
                audioOnlyOverlay.style.display = 'flex';
            } else {
                localVideo.style.display = 'block';
                remoteVideo.style.display = 'block';
                audioOnlyOverlay.style.display = 'none';
            }
        } else {
            console.warn("Pas de piste vidéo locale à basculer. Tenter d'ajouter une piste vidéo n'est pas implémenté ici.");
            showCustomAlert("Fonctionnalité de passage en appel vidéo non disponible pour cet appel.", "warning");
        }
    }
}


const debounce = (func, delay) => {
    let timeout;
    return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), delay);
    };
};


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

    callsList.innerHTML = '';

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
        const participantAvatar = participant ? (participant.profile_picture || null) : null;
        const callTypeIcon = callTypeIcons[call.call_type] || 'fas fa-phone';

        let statusText = '';
        let statusIconClass = '';
        let callDirectionIcon = '';

        if (isCaller) {
            callDirectionIcon = '<i class="fas fa-arrow-up text-success me-1"></i>';
            statusText = callStatusDisplay[call.status]?.text_caller || callStatusDisplay[call.status]?.text || call.status;
            statusIconClass = callStatusDisplay[call.status]?.icon || 'fas fa-question-circle text-warning';
            if (call.status === 'missed') {
                callDirectionIcon = '<i class="fas fa-arrow-up text-warning me-1"></i>';
            }
        } else {
            callDirectionIcon = '<i class="fas fa-arrow-down text-info me-1"></i>';
            statusText = callStatusDisplay[call.status]?.text_receiver || callStatusDisplay[call.status]?.text || call.status;
            statusIconClass = callStatusDisplay[call.status]?.icon || 'fas fa-question-circle text-warning';
            if (call.status === 'missed') {
                callDirectionIcon = '<i class="fas fa-arrow-down text-danger me-1"></i>';
            }
        }

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
                            <i class="${callTypeIcons[call.call_type]}"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        callsList.insertAdjacentHTML('beforeend', callCard);
    });

    document.querySelectorAll('.replay-call-btn').forEach(button => {
        button.addEventListener('click', function() {
            const contactId = this.dataset.contactId;
            const callType = this.dataset.callType;
            const contactName = this.dataset.contactName;

            selectedContactIdInput.value = contactId;
            contactSearchInput.value = contactName;
            startCallButton.disabled = false;

            document.querySelectorAll('#contactListForCall .list-group-item').forEach(item => item.classList.remove('active'));
            if (contactListForCall.querySelector(`[data-contact-id="${contactId}"]`)) {
                contactListForCall.querySelector(`[data-contact-id="${contactId}"]`).classList.add('active');
            }


            document.querySelector(`input[name="call_type"][value="${callType}"]`).checked = true;

            initiateCallModal.show();
        });
    });
}


const fetchUsersForCall = debounce(async (query) => {
    if (query.length < 2) {
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
}, 300);


function renderContactList(users) {
    contactListForCall.innerHTML = '';
    if (users.length === 0) {
        contactListForCall.innerHTML = '<p class="text-muted text-center p-2">Aucun contact trouvé.</p>';
        return;
    }

    users.forEach(user => {
        if (user.id === currentLoggedInUserId) {
            return;
        }

        const avatarHtml = user.profile_picture
            ? `<img src="${user.profile_picture}" alt="${user.name}" class="avatar-thumbnail me-3">`
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
            startCallButton.disabled = false;
            contactSearchInput.value = user.name;
            contactListForCall.innerHTML = '';
        });
        contactListForCall.appendChild(listItem);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    if (initiateCallForm) {
        initiateCallForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await initiateCall();
        });
    }

    if (rejectCallButton) {
        rejectCallButton.addEventListener('click', rejectCall);
    }

    if (acceptCallButton) {
        acceptCallButton.addEventListener('click', acceptCall);
    }

    if (hangupButton) {
        hangupButton.addEventListener('click', endCall);
    }

    if (muteToggleButton) {
        muteToggleButton.addEventListener('click', toggleMute);
    }

    if (videoToggleButton) {
        videoToggleButton.addEventListener('click', toggleVideo);
    }

    if (contactSearchInput) {
        contactSearchInput.addEventListener('input', (e) => fetchUsersForCall(e.target.value));
    }

    if (clearSearchButton) {
        clearSearchButton.addEventListener('click', () => {
            callSearchInput.value = '';
            fetchCallHistory();
        });
    }

    if (callSearchInput) {
        callSearchInput.addEventListener('input', debounce((e) => fetchCallHistory(e.target.value), 300));
    }

    fetchCallHistory();

    if (currentLoggedInUserId && window.Echo) {
        console.log(`Listening for events on private channel users.${currentLoggedInUserId}`);
        window.Echo.private(`users.${currentLoggedInUserId}`)
            .listen('IncomingCall', async (e) => {
                console.log('IncomingCall event received:', e.call);
                currentCall = e.call;
                if (currentCall.status === 'initiated') {
                    showIncomingCallModal(currentCall);
                }
            })
            .listen('CallAccepted', async (e) => {
                console.log('CallAccepted event received:', e.call);
                if (currentCall && currentCall.call_uuid === e.call.call_uuid) {
                    currentCall = e.call;
                    hideIncomingCallModal();

                    const participantName = currentCall.caller_id === currentLoggedInUserId
                                            ? (currentCall.receiver ? currentCall.receiver.name : 'Destinataire')
                                            : (currentCall.caller ? currentCall.caller.name : 'Appelant');

                    if (!activeCallModal._isShown) {
                        showCallModal(participantName, currentCall.call_type, 'Appel en cours');
                    } else {
                        activeCallStatusText.textContent = 'Appel en cours';
                    }
                    
                    if (peerConnection && peerConnection.remoteDescription === null && e.call.answer_payload) {
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(e.call.answer_payload));
                        console.log('Réponse distante définie (après acceptation).');
                    }
                    startCallTimer();
                    showCustomAlert('Appel accepté par le destinataire !', 'success');
                }
            })
            .listen('CallRejected', (e) => {
                console.log('CallRejected event received:', e.call);
                // S'assurer que l'événement concerne l'appel en cours
                if (currentCall && currentCall.call_uuid === e.call.call_uuid) {
                    showCustomAlert('L\'appel a été rejeté.', 'danger');
                    hideIncomingCallModal(); // Cache la modale d'appel entrant si elle était affichée
                    hideCallModal(); // Cache la modale d'appel actif si elle était affichée
                    resetCallState(); // Réinitialise l'état local de l'appel
                    fetchCallHistory(); // Rafraîchit l'historique pour montrer le statut mis à jour
                }
            })
            .listen('CallEnded', (e) => {
                console.log('CallEnded event received:', e.call);
                // S'assurer que l'événement concerne l'appel en cours
                if (currentCall && currentCall.call_uuid === e.call.call_uuid) {
                    showCustomAlert('L\'appel a été terminé.', 'info');
                    hideIncomingCallModal(); // Cache la modale d'appel entrant si elle était affichée
                    hideCallModal(); // Cache la modale d'appel actif si elle était affichée
                    resetCallState(); // Réinitialise l'état local de l'appel
                    fetchCallHistory(); // Rafraîchit l'historique pour montrer le statut mis à jour
                }
            })
            .listen('SignalingMessage', async (e) => {
                console.log('SignalingMessage event received:', e.message);
                if (!peerConnection) {
                    console.warn('PeerConnection non initialisé pour le message de signalisation.');
                    return;
                }

                if (e.message.type === 'offer') {
                    if (!peerConnection.remoteDescription) {
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(e.message.payload));
                        const answer = await peerConnection.createAnswer();
                        await peerConnection.setLocalDescription(answer);
                        sendSignalingMessage('answer', answer);
                    }
                } else if (e.message.type === 'answer') {
                    if (peerConnection.remoteDescription === null) {
                         await peerConnection.setRemoteDescription(new RTCSessionDescription(e.message.payload));
                    }
                } else if (e.message.type === 'ice-candidate') {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(e.message.payload));
                }
            });
    } else {
        console.warn('Echo ou currentLoggedInUserId non disponible. Les événements temps réel ne seront pas écoutés.');
    }
});
