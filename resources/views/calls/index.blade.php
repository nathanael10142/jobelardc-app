{{-- resources/views/calls/index.blade.php --}}
@extends('layouts.user')

@section('title', 'Historique des Appels - Jobela RDC')

@section('content')
@php
    use Illuminate\Support\Str;
    // Définir des icônes pour les types d'appel et les statuts
    $callTypeIcons = [
        'audio' => 'fas fa-phone',
        'video' => 'fas fa-video',
    ];
    $callStatusIcons = [
        'initiated' => 'fas fa-arrow-up text-info', // Appel initié (en attente)
        'outgoing' => 'fas fa-arrow-up text-success', // Appel sortant
        'incoming' => 'fas fa-arrow-down text-primary', // Appel entrant
        'accepted' => 'fas fa-check-circle text-success', // Appel accepté
        'rejected' => 'fas fa-times-circle text-danger', // Appel rejeté
        'missed' => 'fas fa-phone-slash text-danger', // Appel manqué
        'ended' => 'fas fa-phone-alt text-muted', // Appel terminé
    ];
@endphp

<div class="content-section p-3" id="main-calls-content">
    <h5 class="mb-3 whatsapp-heading">
        <i class="fas fa-phone-alt me-2"></i> Historique des Appels
    </h5>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        {{-- Zone de recherche WhatsApp pour les appels/contacts --}}
        <form action="{{ route('calls.index') }}" method="GET" class="whatsapp-search-form flex-grow-1 me-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control whatsapp-search-input" placeholder="{{ __('Rechercher appels ou contacts...') }}" value="{{ request('search') }}">
                <button class="btn whatsapp-search-btn" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>

        {{-- Bouton pour initier un nouvel appel (vers une page de sélection de contact par exemple) --}}
        <a href="#" class="btn btn-whatsapp-primary rounded-pill px-4 shadow-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#initiateCallModal">
            <i class="fas fa-phone-volume me-2"></i> Nouvel Appel
        </a>
    </div>

    {{-- Liste des Appels (Exemple de structure, à populer par le contrôleur) --}}
    <div class="calls-container">
        @forelse ($calls as $call) {{-- La variable $calls doit être passée par le contrôleur --}}
            <div class="card call-card mb-3 shadow-sm">
                <div class="card-body d-flex align-items-center p-3">
                    {{-- Avatar du contact --}}
                    <div class="me-3 flex-shrink-0">
                        @php
                            $contact = $call->otherParticipant; // Supposons une relation 'otherParticipant'
                            $avatarHtml = '';

                            if ($contact) {
                                $avatarPath = $contact->profile_picture ?? null;
                                $isExternal = $avatarPath && Str::startsWith($avatarPath, ['http://', 'https://']);

                                if ($avatarPath) {
                                    $avatarSrc = $isExternal ? $avatarPath : asset('storage/' . $avatarPath);
                                    $avatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil de ' . $contact->name . '" class="avatar-thumbnail">';
                                } else {
                                    // Fallback to initials avatar if no profile picture
                                    $initials = '';
                                    if ($contact->name) {
                                        $words = explode(' ', $contact->name);
                                        foreach ($words as $word) {
                                            $initials .= strtoupper(substr($word, 0, 1));
                                        }
                                        if (strlen($initials) > 2) {
                                            $initials = substr($initials, 0, 2);
                                        }
                                    } else {
                                        $initials = '??';
                                    }
                                    // Générer une couleur cohérente basée sur l'email ou l'ID de l'utilisateur
                                    $bgColor = '#' . substr(md5($contact->email ?? $contact->id ?? uniqid()), 0, 6);
                                    $avatarHtml = '<div class="avatar-text-placeholder" style="background-color: ' . $bgColor . ';">' . $initials . '</div>';
                                }
                            } else {
                                // Fallback for anonymous or deleted user
                                $avatarHtml = '<div class="avatar-text-placeholder" style="background-color: #999;"><i class="fas fa-user-circle"></i></div>';
                            }
                        @endphp
                        {!! $avatarHtml !!}
                    </div>

                    {{-- Détails de l'appel --}}
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 profile-name text-truncate">
                                {{ $contact->name ?? 'Utilisateur Inconnu' }}
                            </h6>
                            <small class="text-muted call-time">
                                {{ $call->created_at->locale('fr')->diffForHumans() }}
                            </small>
                        </div>
                        <p class="text-muted small mb-2 call-info">
                            <i class="{{ $callStatusIcons[$call->status] ?? 'fas fa-question-circle text-muted' }} me-1"></i>
                            <span class="me-2">{{ $call->status_text ?? ucfirst($call->status) }}</span>
                            <i class="{{ $callTypeIcons[$call->call_type] ?? 'fas fa-phone text-muted' }} me-1"></i>
                            <span>{{ ucfirst($call->call_type) }}</span>
                        </p>
                        {{-- Ajoutez ici d'autres détails comme la durée si disponible --}}
                        @if ($call->duration)
                            <p class="card-text mb-1 call-duration">
                                <i class="fas fa-clock me-1 text-whatsapp-muted"></i> Durée: {{ gmdate("H:i:s", $call->duration) }}
                            </p>
                        @endif
                    </div>

                    {{-- Bouton de rappel ou d'action --}}
                    <div class="ms-3 flex-shrink-0">
                        <a href="#" class="btn btn-sm btn-outline-whatsapp-green rounded-pill">
                            <i class="fas fa-phone-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info text-center whatsapp-card" role="alert">
                Aucun appel dans votre historique pour le moment.
            </div>
        @endforelse
    </div>
</div>

{{-- Modal pour initier un nouvel appel --}}
<div class="modal fade" id="initiateCallModal" tabindex="-1" aria-labelledby="initiateCallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content whatsapp-card">
            <div class="modal-header whatsapp-heading-modal">
                <h5 class="modal-title" id="initiateCallModalLabel">Initier un Nouvel Appel</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Sélectionnez un contact pour démarrer un appel audio ou vidéo.</p>
                <form id="initiateCallForm">
                    <div class="mb-3">
                        <label for="contactSelect" class="form-label">Sélectionner un contact:</label>
                        <select class="form-select" id="contactSelect" name="receiver_id">
                            <option value="">Chargement des contacts...</option>
                            {{-- Les options seront chargées via JS --}}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type d'appel:</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="call_type" id="audioCall" value="audio" checked>
                                <label class="form-check-label" for="audioCall"><i class="fas fa-phone"></i> Audio</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="call_type" id="videoCall" value="video">
                                <label class="form-check-label" for="videoCall"><i class="fas fa-video"></i> Vidéo</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-whatsapp-primary"><i class="fas fa-phone-volume me-2"></i> Démarrer l'Appel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- NOUVEAU: Modal pour l'appel entrant --}}
<div class="modal fade" id="incomingCallModal" tabindex="-1" aria-labelledby="incomingCallModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content whatsapp-card text-center">
            <div class="modal-header whatsapp-heading-modal">
                <h5 class="modal-title" id="incomingCallModalLabel">Appel Entrant</h5>
            </div>
            <div class="modal-body p-4">
                <div class="incoming-call-avatar mb-3">
                    {{-- Avatar de l'appelant, sera rempli par JS --}}
                    <img src="https://placehold.co/100x100/ccc/white?text=?" alt="Avatar" class="rounded-circle mb-2" id="incomingCallerAvatar" style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <h4 id="incomingCallerName" class="mb-1">Nom de l'appelant</h4>
                <p class="text-muted mb-3" id="incomingCallType">Type d'appel</p>
                <div class="d-flex justify-content-around mt-4">
                    <button type="button" class="btn btn-danger btn-lg rounded-circle mx-2" id="rejectCallButton" style="width: 60px; height: 60px;">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                    <button type="button" class="btn btn-success btn-lg rounded-circle mx-2" id="acceptCallButton" style="width: 60px; height: 60px;">
                        <i class="fas fa-phone-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- NOUVEAU: Modal pour l'appel actif (WebRTC) --}}
<div class="modal fade" id="activeCallModal" tabindex="-1" aria-labelledby="activeCallModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen"> {{-- Plein écran pour une meilleure expérience d'appel --}}
        <div class="modal-content whatsapp-card d-flex flex-column h-100">
            <div class="modal-header whatsapp-heading-modal text-center d-block">
                <h5 class="modal-title" id="activeCallModalLabel">Appel en cours avec <span id="activeCallParticipantName"></span></h5>
                <p class="text-white-50 mb-0" id="callTimer">00:00</p>
            </div>
            <div class="modal-body p-0 flex-grow-1 d-flex flex-column justify-content-center align-items-center bg-dark">
                {{-- Vidéo distante (grand) --}}
                <video id="remoteVideo" autoplay playsinline class="w-100 h-100" style="object-fit: cover; background-color: black;"></video>
                
                {{-- Vidéo locale (petit, en incrustation) --}}
                <video id="localVideo" autoplay playsinline muted class="position-absolute rounded shadow-lg" style="bottom: 20px; right: 20px; width: 120px; height: 90px; object-fit: cover; border: 2px solid white;"></video>

                {{-- Overlay pour l'audio seulement --}}
                <div id="audioOnlyOverlay" class="position-absolute w-100 h-100 d-flex flex-column justify-content-center align-items-center bg-dark text-white" style="top: 0; left: 0; display: none;">
                    <i class="fas fa-phone-alt fa-3x mb-3"></i>
                    <h4 id="audioOnlyParticipantName"></h4>
                    <p class="text-white-50">Appel audio en cours</p>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-center whatsapp-heading-modal py-3">
                <button type="button" class="btn btn-lg btn-secondary rounded-circle mx-2" id="muteToggleButton" title="Couper le micro" style="width: 60px; height: 60px;">
                    <i class="fas fa-microphone"></i>
                </button>
                <button type="button" class="btn btn-lg btn-secondary rounded-circle mx-2" id="videoToggleButton" title="Activer/Désactiver la vidéo" style="width: 60px; height: 60px;">
                    <i class="fas fa-video"></i>
                </button>
                <button type="button" class="btn btn-danger btn-lg rounded-circle mx-2" id="hangupButton" title="Raccrocher" style="width: 60px; height: 60px;">
                    <i class="fas fa-phone-slash"></i>
                </button>
            </div>
        </div>
    </div>
</div>


@endsection

@push('styles')
<style>
    /* WhatsApp Colors and Variables */
    :root {
        --whatsapp-green-dark: #075E54;
        --whatsapp-green-light: #128C7E;
        --whatsapp-blue-seen: #34B7F1;
        --whatsapp-background: #E5DDD5; /* Light background for the page */
        --whatsapp-chat-bg: #E5DDD5;
        --whatsapp-message-sent: #DCF8C6;
        --whatsapp-message-received: #FFFFFF;
        --whatsapp-text-dark: #202C33;
        --whatsapp-text-muted: #667781;
        --whatsapp-border: #E0E0E0; /* Lighter border for cards */
        --whatsapp-card-bg: #FFFFFF; /* White background for cards */
        --whatsapp-light-hover: #F0F0F0;
        --whatsapp-primary-button: #25D366; /* A vibrant green for primary actions */
        --whatsapp-search-bg: #F0F2F5; /* Background for search input */
        --whatsapp-search-border: #D1D7DA; /* Border for search input */
        --whatsapp-icon-color: #667781; /* Color for search icon */
    }

    body {
        background-color: var(--whatsapp-background);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--whatsapp-text-dark);
    }

    .content-section {
        max-width: 800px;
        margin: 0 auto;
        padding-top: 20px !important;
    }

    .whatsapp-heading {
        color: var(--whatsapp-green-dark);
        font-weight: 700;
        display: flex;
        align-items: center;
        margin-bottom: 25px !important;
    }

    .whatsapp-heading-modal {
        background-color: var(--whatsapp-green-dark);
        color: white;
        border-bottom: 1px solid var(--whatsapp-green-dark);
    }

    .btn-close {
        filter: invert(1); /* Makes the close button white */
    }

    /* WhatsApp Search Bar Styles */
    .whatsapp-search-form {
        border-radius: 20px; /* Highly rounded */
        overflow: hidden; /* Ensure content respects border-radius */
        background-color: var(--whatsapp-search-bg);
        border: 1px solid var(--whatsapp-search-border);
    }

    .whatsapp-search-input {
        background-color: transparent; /* No background for input itself */
        border: none;
        box-shadow: none !important; /* Remove focus shadow */
        padding: 0.5rem 1rem;
        color: var(--whatsapp-text-dark);
        border-radius: 20px 0 0 20px; /* Only left side rounded */
    }

    .whatsapp-search-input::placeholder {
        color: var(--whatsapp-text-muted);
        opacity: 0.7;
    }

    .whatsapp-search-input:focus {
        border-color: transparent; /* No border on focus */
        box-shadow: none; /* No shadow on focus */
    }

    .whatsapp-search-btn {
        background-color: transparent; /* No background for button itself */
        border: none;
        color: var(--whatsapp-icon-color);
        padding: 0.5rem 1rem;
        border-radius: 0 20px 20px 0; /* Only right side rounded */
        transition: color 0.2s ease;
    }

    .whatsapp-search-btn:hover {
        color: var(--whatsapp-green-dark); /* Darker green on hover */
    }

    .btn-whatsapp-primary {
        background-color: var(--whatsapp-primary-button);
        border-color: var(--whatsapp-primary-button);
        color: white;
        font-weight: 600;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .btn-whatsapp-primary:hover {
        background-color: var(--whatsapp-green-light);
        border-color: var(--whatsapp-green-light);
        color: white;
    }

    .calls-container {
        padding: 5px; /* Slight padding for the container */
    }

    .call-card {
        background-color: var(--whatsapp-card-bg);
        border: 1px solid var(--whatsapp-border);
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .call-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08); /* Slightly more pronounced shadow */
    }

    .avatar-thumbnail, .avatar-text-placeholder {
        width: 55px; /* Slightly larger avatar */
        height: 55px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--whatsapp-green-light); /* Green border around avatar */
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem; /* For initials/icon */
        font-weight: bold;
        color: white;
        text-transform: uppercase;
        background-color: #ccc; /* Default background for initials */
    }

    .avatar-text-placeholder i {
        font-size: 1.8rem; /* Icon size for default avatar */
    }

    .profile-name {
        font-weight: 600;
        color: var(--whatsapp-green-dark);
        font-size: 1.05rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%; /* Ensures name fits */
    }

    .call-time {
        font-size: 0.8rem;
        color: var(--whatsapp-text-muted);
    }

    .call-info {
        font-weight: 500;
        color: var(--whatsapp-text-muted);
        display: flex;
        align-items: center;
    }

    .call-info i {
        font-size: 0.9rem;
    }

    .call-duration {
        font-size: 0.95rem;
        color: var(--whatsapp-text-muted);
        display: flex;
        align-items: center;
    }

    .call-duration i {
        color: var(--whatsapp-text-muted);
    }

    .btn-outline-whatsapp-green {
        color: var(--whatsapp-green-light);
        border-color: var(--whatsapp-green-light);
        font-weight: 600;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }

    .btn-outline-whatsapp-green:hover {
        background-color: var(--whatsapp-green-light);
        color: white;
        border-color: var(--whatsapp-green-light);
    }

    .alert-info.whatsapp-card { /* Style for empty state alert */
        background-color: var(--whatsapp-message-received);
        border-color: var(--whatsapp-border);
        color: var(--whatsapp-text-dark);
        border-radius: 12px;
        padding: 1.5rem;
    }

    /* Styles pour les modaux d'appel */
    #incomingCallModal .modal-content, #activeCallModal .modal-content {
        border-radius: 15px; /* Plus arrondi pour les modaux d'appel */
        overflow: hidden;
    }
    #incomingCallModal .incoming-call-avatar img {
        border: 4px solid var(--whatsapp-green-light); /* Bordure plus prononcée */
    }
    #activeCallModal .modal-body {
        position: relative; /* Pour positionner les vidéos */
    }
    #localVideo {
        z-index: 10; /* Pour qu'elle soit au-dessus de la vidéo distante */
    }
    #audioOnlyOverlay {
        z-index: 15; /* Au-dessus des vidéos quand l'audio est seul */
    }

    /* Responsive adjustments */
    @media (max-width: 767px) {
        .content-section {
            padding: 10px;
        }

        .whatsapp-heading {
            font-size: 1.2rem;
            margin-bottom: 15px !important;
        }

        .whatsapp-search-form {
            margin-right: 1rem !important;
        }

        .btn-whatsapp-primary {
            padding: 0.6rem 1rem !important;
            font-size: 0.9rem !important;
            white-space: nowrap;
        }

        .avatar-thumbnail, .avatar-text-placeholder {
            width: 45px;
            height: 45px;
            font-size: 1.3rem;
        }

        .avatar-text-placeholder i {
            font-size: 1.6rem;
        }

        .profile-name {
            font-size: 0.9rem;
        }

        .call-time {
            font-size: 0.75rem;
        }

        .call-info, .call-duration {
            font-size: 0.85rem;
        }

        .btn-outline-whatsapp-green {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Référence aux éléments du DOM pour les appels
    const initiateCallModal = document.getElementById('initiateCallModal');
    const contactSelect = document.getElementById('contactSelect');
    const initiateCallForm = document.getElementById('initiateCallForm');

    // Éléments du modal d'appel entrant
    const incomingCallModal = new bootstrap.Modal(document.getElementById('incomingCallModal'));
    const incomingCallerName = document.getElementById('incomingCallerName');
    const incomingCallType = document.getElementById('incomingCallType');
    const incomingCallerAvatar = document.getElementById('incomingCallerAvatar');
    const acceptCallButton = document.getElementById('acceptCallButton');
    const rejectCallButton = document.getElementById('rejectCallButton');

    // Éléments du modal d'appel actif
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

    // Variables globales pour WebRTC
    let localStream;
    let peerConnection;
    let currentCallId = null;
    let currentCallerId = null;
    let currentCallType = null;
    let callInterval; // Pour le timer d'appel
    let callStartTime; // Pour calculer la durée

    // Configuration ICE Servers (STUN/TURN) - Indispensable pour WebRTC
    // Vous pouvez utiliser des serveurs gratuits comme Google's STUN server, ou configurer les vôtres.
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
        currentCallerId = callerId; // Stocke l'ID de l'appelant pour la réponse
        currentCallType = callType; // Stocke le type d'appel

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
        localVideo.srcObject = null;
        remoteVideo.srcObject = null;
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }
        if (peerConnection) {
            peerConnection.close();
        }
        localStream = null;
        peerConnection = null;
        currentCallId = null;
        currentCallerId = null;
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
                await sendSignal('ice-candidate', event.candidate);
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
    async function sendSignal(type, payload) {
        try {
            const response = await fetch("{{ route('calls.signal') }}", { // Nouvelle route à créer
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    call_id: currentCallId,
                    receiver_id: (window.Laravel.user.id === currentCallerId) ? currentReceiverId : currentCallerId, // L'autre participant
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
        await sendSignal('offer', offer);

        // 5. Afficher l'UI d'appel actif (en attente)
        showActiveCallUI(contactSelect.options[contactSelect.selectedIndex].textContent, callType);
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
            const response = await fetch("{{ route('calls.accept') }}", {
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
                // Si on ne peut pas obtenir les médias, rejeter l'appel ou gérer l'erreur
                await rejectCall(); // Ou une gestion d'erreur plus fine
                return;
            }

            // Créer la connexion WebRTC
            createPeerConnection(false); // isCaller = false

            // Attendre l'offre SDP de l'appelant (qui sera reçue via le canal Pusher)
            // Cette partie sera gérée par l'écouteur d'événement 'signal'

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
            const response = await fetch("{{ route('calls.reject') }}", {
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
            const response = await fetch("{{ route('calls.end') }}", {
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
            location.reload(); 
        } catch (error) {
            console.error('Error ending call:', error);
            alert('Erreur lors de la fin de l\'appel.');
        }
    }

    // --- Fonctions de contrôle d'appel ---
    muteToggleButton.addEventListener('click', () => {
        if (localStream) {
            localStream.getAudioTracks().forEach(track => {
                track.enabled = !track.enabled;
                muteToggleButton.querySelector('i').className = track.enabled ? 'fas fa-microphone' : 'fas fa-microphone-slash';
                console.log('Microphone toggled:', track.enabled);
            });
        }
    });

    videoToggleButton.addEventListener('click', () => {
        if (localStream) {
            localStream.getVideoTracks().forEach(track => {
                track.enabled = !track.enabled;
                videoToggleButton.querySelector('i').className = track.enabled ? 'fas fa-video' : 'fas fa-video-slash';
                localVideo.style.display = track.enabled ? 'block' : 'none';
                console.log('Video toggled:', track.enabled);
            });
        }
    });

    hangupButton.addEventListener('click', () => {
        if (currentCallId && window.Laravel.user.id && currentCallerId) {
            // Déterminer l'ID de l'autre participant
            const otherParticipantId = (window.Laravel.user.id === currentCallerId) ? currentReceiverId : currentCallerId;
            const duration = Math.floor((Date.now() - callStartTime) / 1000);
            endCall(currentCallId, otherParticipantId, duration);
        } else {
            console.warn('Cannot hang up: call ID or participant ID missing.');
            hideActiveCallUI(); // Forcer la fermeture de l'UI si les IDs sont manquants
        }
    });

    // --- Événements DOM et Initialisation ---
    document.addEventListener('DOMContentLoaded', function() {
        // ... (Votre logique setActiveLink existante) ...

        // Charger les utilisateurs lorsque le modal d'initiation est affiché
        if (initiateCallModal) {
            initiateCallModal.addEventListener('show.bs.modal', loadUsersForCall);
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
                    const response = await fetch("{{ route('calls.initiate') }}", {
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
                        const modal = bootstrap.Modal.getInstance(initiateCallModal);
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

        // --- Écouteurs d'événements Laravel Echo pour les appels ---
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
                        // L'appelant doit maintenant créer l'Answer SDP et l'envoyer
                        // Non, l'appelant a déjà envoyé l'Offer. Le receveur envoie l'Answer.
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

                    if (!peerConnection) {
                        console.warn('PeerConnection not initialized for signal. Initializing...');
                        // Si peerConnection n'est pas initialisé, c'est probablement le receveur qui vient de recevoir l'offre
                        // et n'a pas encore appelé acceptCall.
                        // Cette situation est gérée par handleIncomingCall -> acceptCall.
                        // Ici, nous nous attendons à ce que peerConnection soit déjà créé.
                        // Si le signal arrive avant que acceptCall n'ait créé le peerConnection,
                        // il faut le gérer, mais pour l'instant, on suppose l'ordre.
                        return; // Ou gérer la création ici si c'est le premier signal (l'offre)
                    }

                    try {
                        if (e.type === 'offer') {
                            // C'est le destinataire qui reçoit l'offre de l'appelant
                            if (peerConnection.signalingState !== 'stable') {
                                console.warn('PeerConnection is not stable, waiting for current signaling to complete.');
                                // Gérer le cas où l'état n'est pas stable (ex: si une autre offre est en cours)
                                // Vous pouvez mettre en file d'attente les offres ou les ignorer
                                return;
                            }
                            await peerConnection.setRemoteDescription(new RTCSessionDescription(e.payload));
                            console.log('SDP Offer set as remote description.');

                            // Créer la réponse (Answer)
                            const answer = await peerConnection.createAnswer();
                            await peerConnection.setLocalDescription(answer);
                            console.log('SDP Answer created and set as local description:', answer);

                            // Envoyer la réponse à l'appelant
                            await sendSignal('answer', answer);

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
    });

    // Fonction pour charger les utilisateurs dans le sélecteur de contact du modal
    async function loadUsersForCall() {
        console.log('Loading users for call modal...');
        contactSelect.innerHTML = '<option value="">Chargement des contacts...</option>';

        try {
            const response = await fetch("{{ route('chats.searchUsers') }}?query=", {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
            }

            const data = await response.json();
            const users = data.users;
            console.log('Users received for call modal:', users);

            contactSelect.innerHTML = '<option value="">Sélectionnez un contact</option>';
            if (Array.isArray(users) && users.length > 0) {
                users.forEach(user => {
                    if (user.id !== window.Laravel.user.id) { // Exclure l'utilisateur actuel
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
</script>
@endpush
