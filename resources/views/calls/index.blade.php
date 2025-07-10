{{-- resources/views/calls/index.blade.php --}}
@extends('layouts.user')

@section('title', 'Historique des Appels - Jobela RDC')

{{-- La balise <body> est gérée par le layout parent (layouts.user.blade.php) --}}

@section('content')
@php
    use Illuminate\Support\Str;
    // Définir des icônes pour les types d'appel et les statuts
    $callTypeIcons = [
        'audio' => 'fas fa-phone',
        'video' => 'fas fa-video',
    ];
    // Ajout de nouveaux statuts et affinage des icônes/textes pour la clarté
    $callStatusDisplay = [
        'initiated' => [
            'icon' => 'fas fa-hourglass-half text-info', // En attente
            'text_caller' => 'Appel en attente...',
            'text_receiver' => 'Appel entrant',
        ],
        'accepted' => [
            'icon' => 'fas fa-check-circle text-success', // Appel accepté
            'text' => 'Appel terminé', // Le texte "terminé" sera affiché avec la durée
        ],
        'rejected' => [
            'icon' => 'fas fa-times-circle text-danger', // Appel rejeté
            'text_caller' => 'Appel rejeté',
            'text_receiver' => 'Appel rejeté',
        ],
        'missed' => [
            'icon' => 'fas fa-phone-slash text-danger', // Appel manqué
            'text_caller' => 'Appel non répondu',
            'text_receiver' => 'Appel manqué',
        ],
        'ended' => [
            'icon' => 'fas fa-phone-alt text-muted', // Appel terminé (pour des appels qui ont eu une durée)
            'text' => 'Appel terminé',
        ],
        'cancelled' => [ // Nouveau statut pour quand l'appelant annule avant connexion
            'icon' => 'fas fa-ban text-secondary',
            'text' => 'Appel annulé',
        ],
    ];
@endphp

    {{-- Message d'alerte personnalisé (remplace alert() et sessions flash) --}}
    <div id="customAlert" class="alert alert-danger fixed-top text-center" style="display:none; z-index:9999; margin-top: 20px;">
        <span id="customAlertMessage"></span>
        <button type="button" class="btn-close" onclick="document.getElementById('customAlert').style.display='none';" aria-label="Close"></button>
    </div>

<div class="content-section p-3" id="main-calls-content">
    <h5 class="mb-3 whatsapp-heading">
        <i class="fas fa-phone-alt me-2"></i> Historique des Appels
    </h5>

    {{-- Zone de recherche WhatsApp pour les appels/contacts --}}
    <form id="callSearchForm" class="whatsapp-search-form flex-grow-1 me-3">
        <div class="input-group">
            <input type="text" name="search" id="callSearchInput" class="form-control whatsapp-search-input" placeholder="{{ __('Rechercher appels ou contacts...') }}" value="{{ request('search') }}">
            <button class="btn whatsapp-search-btn" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>

    <div class="d-flex justify-content-end align-items-center mb-3">
        {{-- Bouton pour initier un nouvel appel (vers une page de sélection de contact par exemple) --}}
        <a href="#" class="btn btn-whatsapp-primary rounded-pill px-4 shadow-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#initiateCallModal">
            <i class="fas fa-phone-volume me-2"></i> Nouvel Appel
        </a>
    </div>

    {{-- Liste des Appels (Sera populée dynamiquement par JavaScript) --}}
    <div class="calls-container" id="callsList">
        {{-- Les cartes d'appel seront injectées ici par JavaScript --}}
        <div class="alert alert-info text-center whatsapp-card" role="alert">
            <i class="fas fa-spinner fa-spin me-2"></i> Chargement de l'historique des appels...
        </div>
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
                        <label for="contactSearchInput" class="form-label">Rechercher un contact:</label>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control whatsapp-search-input" id="contactSearchInput" placeholder="Rechercher...">
                            <button class="btn whatsapp-search-btn" type="button" id="clearSearchButton"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="list-group" id="contactListForCall">
                            {{-- Les contacts seront chargés ici dynamiquement par JavaScript --}}
                            <p class="text-muted text-center p-2">Commencez à taper pour rechercher des contacts...</p>
                        </div>
                        <input type="hidden" name="receiver_id" id="selectedContactId">
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
                        <button type="submit" class="btn btn-whatsapp-primary" id="startCallButton" disabled><i class="fas fa-phone-volume me-2"></i> Démarrer l'Appel</button>
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
                    {{-- Avatar de l'appelant, sera rempli par JS. L'image est un placeholder initial. --}}
                    <img src="https://placehold.co/100x100/ccc/white?text=?" alt="Avatar" class="rounded-circle mb-2" id="incomingCallerAvatar" style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <h4 id="incomingCallerName" class="mb-1">Nom de l'appelant</h4>
                <p class="text-muted mb-3" id="incomingCallType">Type d'appel</p>
                <div class="d-flex justify-content-around mt-4">
                    <button type="button" class="btn btn-danger btn-lg rounded-circle mx-2" id="rejectCallButton" title="Rejeter" style="width: 60px; height: 60px;">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                    <button type="button" class="btn btn-success btn-lg rounded-circle mx-2" id="acceptCallButton" title="Accepter" style="width: 60px; height: 60px;">
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

    html, body {
        height: 100%;
        width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden; /* Prevent horizontal scrolling */
        overflow-y: auto; /* Allow vertical scrolling for the whole page if needed */
        box-sizing: border-box; /* Include padding/border in element's total width and height */
    }

    body {
        background-color: var(--whatsapp-background);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--whatsapp-text-dark);
        display: flex;
        flex-direction: column;
        min-height: 100vh; /* Ensure body takes at least full viewport height */
    }

    #app {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        width: 100%;
    }

    .content-section {
        flex-grow: 1; /* Permet à la section de contenu de prendre la hauteur disponible */
        overflow-y: auto; /* Active le défilement dans cette section */
        max-width: 800px;
        margin: 0 auto;
        padding-top: 20px !important;
        padding-bottom: 20px; /* Add some bottom padding */
        background-color: var(--whatsapp-background);
        box-sizing: border-box;
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
        margin-bottom: 20px; /* Space below search bar */
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); /* Subtle shadow */
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
        min-width: 0; /* Allow shrinking */
    }

    .call-time {
        font-size: 0.8rem;
        color: var(--whatsapp-text-muted);
        flex-shrink: 0; /* Prevent shrinking */
    }

    .call-info {
        font-weight: 500;
        color: var(--whatsapp-text-muted);
        display: flex;
        align-items: center;
        flex-wrap: wrap; /* Allow wrapping if content is too long */
        min-width: 0; /* Allow shrinking */
    }

    .call-info i {
        font-size: 0.9rem;
    }

    .call-duration {
        font-size: 0.95rem;
        color: var(--whatsapp-text-muted);
        display: flex;
        align-items: center;
        flex-shrink: 0; /* Prevent shrinking */
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

    /* Styles spécifiques pour la modale d'initiation d'appel */
    #initiateCallModal .whatsapp-search-input {
        border-radius: 20px; /* Plus arrondi pour l'input de recherche dans la modale */
        border: 1px solid var(--whatsapp-search-border);
    }
    #initiateCallModal .input-group .whatsapp-search-input {
        border-radius: 20px 0 0 20px; /* S'assurer que le coin droit est bien géré avec le bouton */
    }
    #initiateCallModal .input-group .whatsapp-search-btn {
        border-radius: 0 20px 20px 0;
        border: 1px solid var(--whatsapp-search-border);
        border-left: none; /* Supprimer la double bordure */
    }

    .list-group-item {
        background-color: var(--whatsapp-card-bg);
        border: none;
        border-bottom: 1px solid var(--whatsapp-border);
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .list-group-item:last-child {
        border-bottom: none;
    }
    .list-group-item:hover {
        background-color: var(--whatsapp-light-hover);
    }
    .list-group-item.active {
        background-color: var(--whatsapp-green-light);
        color: white;
    }
    .list-group-item .avatar-thumbnail, .list-group-item .avatar-text-placeholder {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
        border: 1px solid var(--whatsapp-border); /* Moins prononcé dans la liste */
    }
    .list-group-item.active .avatar-thumbnail, .list-group-item.active .avatar-text-placeholder {
        border-color: white; /* Bordure blanche quand sélectionné */
    }
    .list-group-item .user-name {
        font-weight: 500;
        color: var(--whatsapp-text-dark);
    }
    .list-group-item.active .user-name {
        color: white;
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
{{-- IMPORTANT: Assurez-vous que bootstrap.bundle.min.js est chargé dans votre layout principal (layouts.user.blade.php) --}}
{{-- Si ce n'est pas le cas, décommentez la ligne ci-dessous, mais il est préférable de le mettre dans le layout. --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> --}}

{{-- Votre script calls.js qui contient toute la logique WebRTC et WebSocket. --}}
{{-- Utilisez @vite('resources/js/calls.js') si vous utilisez Vite (recommandé pour Laravel 9+) --}}
@vite('resources/js/calls.js')
@endpush
