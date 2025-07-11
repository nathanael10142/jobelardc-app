@extends('layouts.user')

@section('title', 'Historique des Appels - Jobela RDC')

@section('content')
<div id="customAlert" class="alert alert-danger fixed-top text-center" style="display:none; z-index:9999; margin-top: 20px;">
    <span id="customAlertMessage"></span>
    <button type="button" class="btn-close" onclick="document.getElementById('customAlert').style.display='none';" aria-label="Close"></button>
</div>

<div class="content-section p-3" id="main-calls-content">
    <h5 class="mb-3 whatsapp-heading">
        <i class="fas fa-phone-alt me-2"></i> Historique des Appels
    </h5>

    <form id="callSearchForm" class="whatsapp-search-form flex-grow-1 me-3">
        <div class="input-group">
            <input type="text" name="search" id="callSearchInput" class="form-control whatsapp-search-input" placeholder="{{ __('Rechercher appels ou contacts...') }}" value="{{ request('search') }}">
            <button class="btn whatsapp-search-btn" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>

    <div class="d-flex justify-content-end align-items-center mb-3">
        <a href="#" class="btn btn-whatsapp-primary rounded-pill px-4 shadow-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#initiateCallModal">
            <i class="fas fa-phone-volume me-2"></i> Nouvel Appel
        </a>
    </div>

    <div class="calls-container" id="callsList">
        <div class="alert alert-info text-center whatsapp-card" role="alert">
            <i class="fas fa-spinner fa-spin me-2"></i> Chargement de l'historique des appels...
        </div>
    </div>
</div>

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

<div class="modal fade" id="incomingCallModal" tabindex="-1" aria-labelledby="incomingCallModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content whatsapp-card text-center">
            <div class="modal-header whatsapp-heading-modal">
                <h5 class="modal-title" id="incomingCallModalLabel">Appel Entrant</h5>
            </div>
            <div class="modal-body p-4">
                <div class="incoming-call-avatar mb-3">
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

<div class="modal fade" id="activeCallModal" tabindex="-1" aria-labelledby="activeCallModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content whatsapp-card d-flex flex-column h-100">
            <div class="modal-header whatsapp-heading-modal text-center d-block">
                {{-- Modifié ici pour inclure le statut de l'appel --}}
                <h5 class="modal-title" id="activeCallModalLabel">
                    <span id="activeCallStatusText"></span> avec <span id="activeCallParticipantName"></span>
                </h5>
                <p class="text-white-50 mb-0" id="callTimer">00:00</p>
            </div>
            <div class="modal-body p-0 flex-grow-1 d-flex flex-column justify-content-center align-items-center bg-dark">
                <video id="remoteVideo" autoplay playsinline class="w-100 h-100" style="object-fit: cover; background-color: black;"></video>

                <video id="localVideo" autoplay playsinline muted class="position-absolute rounded shadow-lg" style="bottom: 20px; right: 20px; width: 120px; height: 90px; object-fit: cover; border: 2px solid white;"></video>

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
    :root {
        --whatsapp-green-dark: #075E54;
        --whatsapp-green-light: #128C7E;
        --whatsapp-blue-seen: #34B7F1;
        --whatsapp-background: #E5DDD5;
        --whatsapp-chat-bg: #E5DDD5;
        --whatsapp-message-sent: #DCF8C6;
        --whatsapp-message-received: #FFFFFF;
        --whatsapp-text-dark: #202C33;
        --whatsapp-text-muted: #667781;
        --whatsapp-border: #E0E0E0;
        --whatsapp-card-bg: #FFFFFF;
        --whatsapp-light-hover: #F0F0F0;
        --whatsapp-primary-button: #25D366;
        --whatsapp-search-bg: #F0F2F5;
        --whatsapp-search-border: #D1D7DA;
        --whatsapp-icon-color: #667781;
    }

    html, body {
        height: 100%;
        width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        overflow-y: auto;
        box-sizing: border-box;
    }

    body {
        background-color: var(--whatsapp-background);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--whatsapp-text-dark);
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    #app {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        width: 100%;
    }

    .content-section {
        flex-grow: 1;
        overflow-y: auto;
        max-width: 800px;
        margin: 0 auto;
        padding-top: 20px !important;
        padding-bottom: 20px;
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
        filter: invert(1);
    }

    .whatsapp-search-form {
        border-radius: 20px;
        overflow: hidden;
        background-color: var(--whatsapp-search-bg);
        border: 1px solid var(--whatsapp-search-border);
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .whatsapp-search-input {
        background-color: transparent;
        border: none;
        box-shadow: none !important;
        padding: 0.5rem 1rem;
        color: var(--whatsapp-text-dark);
        border-radius: 20px 0 0 20px;
    }

    .whatsapp-search-input::placeholder {
        color: var(--whatsapp-text-muted);
        opacity: 0.7;
    }

    .whatsapp-search-input:focus {
        border-color: transparent;
        box-shadow: none;
    }

    .whatsapp-search-btn {
        background-color: transparent;
        border: none;
        color: var(--whatsapp-icon-color);
        padding: 0.5rem 1rem;
        border-radius: 0 20px 20px 0;
        transition: color 0.2s ease;
    }

    .whatsapp-search-btn:hover {
        color: var(--whatsapp-green-dark);
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
        padding: 5px;
    }

    .call-card {
        background-color: var(--whatsapp-card-bg);
        border: 1px solid var(--whatsapp-border);
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .call-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .avatar-thumbnail, .avatar-text-placeholder {
        width: 55px;
        height: 55px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--whatsapp-green-light);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: bold;
        color: white;
        text-transform: uppercase;
        background-color: #ccc;
    }

    .avatar-text-placeholder i {
        font-size: 1.8rem;
    }

    .profile-name {
        font-weight: 600;
        color: var(--whatsapp-green-dark);
        font-size: 1.05rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        min-width: 0;
    }

    .call-time {
        font-size: 0.8rem;
        color: var(--whatsapp-text-muted);
        flex-shrink: 0;
    }

    .call-info {
        font-weight: 500;
        color: var(--whatsapp-text-muted);
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        min-width: 0;
    }

    .call-info i {
        font-size: 0.9rem;
    }

    .call-duration {
        font-size: 0.95rem;
        color: var(--whatsapp-text-muted);
        display: flex;
        align-items: center;
        flex-shrink: 0;
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

    .alert-info.whatsapp-card {
        background-color: var(--whatsapp-message-received);
        border-color: var(--whatsapp-border);
        color: var(--whatsapp-text-dark);
        border-radius: 12px;
        padding: 1.5rem;
    }

    #incomingCallModal .modal-content, #activeCallModal .modal-content {
        border-radius: 15px;
        overflow: hidden;
    }
    #incomingCallModal .incoming-call-avatar img {
        border: 4px solid var(--whatsapp-green-light);
    }
    #activeCallModal .modal-body {
        position: relative;
    }
    #localVideo {
        z-index: 10;
    }
    #audioOnlyOverlay {
        z-index: 15;
    }

    #initiateCallModal .whatsapp-search-input {
        border-radius: 20px;
        border: 1px solid var(--whatsapp-search-border);
    }
    #initiateCallModal .input-group .whatsapp-search-input {
        border-radius: 20px 0 0 20px;
    }
    #initiateCallModal .input-group .whatsapp-search-btn {
        border-radius: 0 20px 20px 0;
        border: 1px solid var(--whatsapp-search-border);
        border-left: none;
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
        border: 1px solid var(--whatsapp-border);
    }
    .list-group-item.active .avatar-thumbnail, .list-group-item.active .avatar-text-placeholder {
        border-color: white;
    }
    .list-group-item .user-name {
        font-weight: 500;
        color: var(--whatsapp-text-dark);
    }
    .list-group-item.active .user-name {
        color: white;
    }

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
@vite('resources/js/calls.js')
@endpush
