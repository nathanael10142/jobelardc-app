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
    // Ce script est maintenant très minimal, car la plupart de la logique est dans calls.js
    document.addEventListener('DOMContentLoaded', function() {
        // La logique setActiveLink peut rester ici si elle est spécifique à cette vue.
        const currentPath = window.location.pathname;
        function setActiveLink() {
            document.querySelectorAll('.whatsapp-tabs .tab-item').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => item.classList.remove('active'));

            if (currentPath.startsWith('{{ route('chats.index', [], false) }}')) {
                document.getElementById('tab-chats').classList.add('active');
            } else if (currentPath.startsWith('{{ route('status.index', [], false) }}')) {
                document.getElementById('tab-status').classList.add('active');
            } else if (currentPath.startsWith('{{ route('calls.index', [], false) }}')) {
                document.getElementById('tab-calls').classList.add('active');
            } else if (currentPath.startsWith('{{ route('camera.index', [], false) }}')) {
                document.querySelector('.camera-icon').classList.add('active');
            } else if (currentPath === '{{ route('home', [], false) }}' || currentPath.startsWith('{{ route('listings.index', [], false) }}')) {
                // No specific tab active for home/listings
            }

            document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
                const itemPath = new URL(item.href).pathname;
                if (itemPath === currentPath) {
                    item.classList.add('active');
                }
            });
        }
        setActiveLink();
    });
</script>
@endpush
