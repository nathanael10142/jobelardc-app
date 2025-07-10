<?php
// Les déclarations 'use' doivent être au tout début du fichier Blade,
// avant tout contenu HTML ou autres directives Blade complexes.
use Illuminate\Support\Str;
?>
@extends('layouts.user')

@section('title', 'Discussions - Jobela RDC')

@section('content')
    {{-- Message d'alerte personnalisé (remplace alert()) --}}
    <div id="customAlert" class="alert alert-danger fixed-top text-center" style="display:none; z-index:9999; margin-top: 20px;">
        <span id="customAlertMessage"></span>
        <button type="button" class="btn-close" onclick="document.getElementById('customAlert').style.display='none';" aria-label="Close"></button>
    </div>

    <div class="content-section p-3">
        <h5 class="mb-3 whatsapp-heading">
            <i class="fas fa-comments me-2"></i> Mes Discussions
        </h5>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Zone de recherche de conversations de type WhatsApp --}}
        <form action="{{ route('chats.index') }}" method="GET" class="whatsapp-search-form mb-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control whatsapp-search-input" placeholder="{{ __('Rechercher une discussion...') }}" value="{{ request('search') }}">
                <button class="btn whatsapp-search-btn" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>

        <div class="conversations-list">
            @forelse ($conversations as $conversation)
                <a href="{{ route('chats.show', $conversation->id) }}" class="conversation-item d-flex align-items-center p-3 mb-2 rounded-3 text-decoration-none">
                    <div class="conversation-avatar-wrapper me-3">
                        @php
                            $displayAvatarHtml = '';
                            $displayName = '';
                            $isGroup = $conversation->is_group;

                            if ($isGroup) {
                                $displayName = $conversation->name ?: 'Groupe de discussion';
                                $displayAvatarHtml = '<div class="avatar-text-placeholder-chat-list group-avatar"><i class="fas fa-users"></i></div>';
                            } else {
                                // Trouver l'autre participant dans une discussion 1-à-1
                                $otherParticipant = $conversation->users->first(fn($u) => $u->id !== Auth::id());

                                if ($otherParticipant) {
                                    $displayName = $otherParticipant->name;
                                    $avatarPath = $otherParticipant->profile_picture ?? null;
                                    $isExternalAvatar = $avatarPath && \Illuminate\Support\Str::startsWith($avatarPath, ['http://', 'https://']);

                                    if ($avatarPath) {
                                        $avatarSrc = $isExternalAvatar ? $avatarPath : asset('storage/' . $avatarPath);
                                        $displayAvatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil de ' . $otherParticipant->name . '" class="avatar-thumbnail-chat-list" onerror="this.onerror=null;this.src=\'https://placehold.co/56x56/ccc/white?text=?\';">';
                                    } else {
                                        // Retour à l'avatar d'initiales si pas de photo de profil
                                        // Assurez-vous que le modèle User a getInitialsAttribute et getAvatarBgColorAttribute
                                        $displayAvatarHtml = '<div class="avatar-text-placeholder-chat-list" style="background-color: ' . ($otherParticipant->avatar_bg_color ?? '#777') . ';">' . ($otherParticipant->initials ?? '??') . '</div>';
                                    }
                                } else {
                                    // Cas de repli pour un scénario inattendu (par exemple, chat avec soi-même, ou utilisateur supprimé)
                                    $displayName = 'Utilisateur inconnu';
                                    $displayAvatarHtml = '<div class="avatar-text-placeholder-chat-list" style="background-color: #777;">??</div>';
                                }
                            }
                        @endphp
                        {!! $displayAvatarHtml !!}
                    </div>
                    <div class="conversation-info flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start"> {{-- Changed align-items-center to align-items-start --}}
                            <h6 class="mb-0 conversation-name">
                                {{ $displayName }}
                            </h6>
                            <div class="d-flex align-items-center flex-shrink-0 ms-auto"> {{-- Added ms-auto to push to right --}}
                                @if ($conversation->lastMessage)
                                    <small class="text-muted conversation-time me-2">{{ $conversation->lastMessage->created_at->diffForHumans() }}</small> {{-- Added me-2 --}}
                                @endif

                                {{-- Badge du nombre de messages non lus --}}
                                @if ($conversation->unread_messages_count > 0)
                                    <span class="badge rounded-pill unread-count-badge">
                                        {{ $conversation->unread_messages_count }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if ($conversation->lastMessage)
                            <p class="mb-0 conversation-last-message @if($conversation->unread_messages_count > 0) text-dark fw-bold @else text-muted @endif">
                                @if($conversation->lastMessage->user_id === Auth::id())
                                    <span class="text-success me-1"><i class="fas fa-check-double"></i> Vous:</span>
                                @endif
                                {{ \Illuminate\Support\Str::limit($conversation->lastMessage->body, 35) }}
                            </p>
                        @else
                            <p class="mb-0 text-muted conversation-last-message">Commencez une conversation !</p>
                        @endif
                    </div>
                </a>
            @empty
                <div class="alert alert-info text-center whatsapp-card" role="alert">
                    Aucune discussion pour le moment.
                    <br>
                    Lancez une nouvelle conversation !
                </div>
            @endforelse
        </div>

        {{-- Bouton flottant pour démarrer une nouvelle discussion --}}
        <div class="floating-action-button">
            <button type="button" class="btn btn-whatsapp-send rounded-circle shadow-lg" title="Nouvelle Discussion" data-bs-toggle="modal" data-bs-target="#newChatModal">
                <i class="fas fa-plus fa-lg"></i>
            </button>
        </div>
    </div>

    {{-- Modale Nouvelle Discussion --}}
    <div class="modal fade" id="newChatModal" tabindex="-1" aria-labelledby="newChatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-whatsapp-green-dark text-white">
                    <h5 class="modal-title" id="newChatModalLabel">Démarrer une nouvelle discussion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="userSearchInput" class="form-control mb-3" placeholder="Rechercher un utilisateur...">
                    <div id="userList" class="list-group">
                        <p class="text-center text-muted py-3">Tapez pour rechercher des utilisateurs...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Variables WhatsApp (définies globalement ou ici) */
        :root {
            --whatsapp-green-dark: #075E54;
            --whatsapp-green-light: #128C7E;
            --whatsapp-blue-seen: #34B7F1;
            --whatsapp-background: #E5DDD5; /* Arrière-plan de la page principale */
            --whatsapp-card-bg: #FFFFFF; /* Arrière-plan de la carte de conversation */
            --whatsapp-light-hover: #F0F0F0; /* Couleur au survol */
            --whatsapp-text-dark: #202C33; /* Texte plus foncé pour une meilleure lisibilité */
            --whatsapp-text-muted: #667781; /* Gris pour les horodatages et les messages lus */
            --whatsapp-border: #E0E0E0; /* Bordure de la carte */
            --whatsapp-unread-badge: #25D366; /* Couleur du badge non lu */
            --whatsapp-search-bg: #F0F2F5; /* Arrière-plan pour le champ de recherche */
            --whatsapp-search-border: #D1D7DA; /* Bordure pour le champ de recherche */
            --whatsapp-icon-color: #667781; /* Couleur de l'icône de recherche */
        }

        html {
            height: 100%;
            width: 100%;
            display: flex; /* Make html a flex container */
            flex-direction: column; /* Stack its children (body) vertically */
        }

        body {
            height: 100%; /* Now body can truly take 100% of html's height */
            min-height: 100vh; /* Ensure it's at least viewport height */
            width: 100%; /* Ensure it takes full width */
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Prevent horizontal scroll */
            overflow-y: auto; /* Allow vertical scroll for the whole page if needed */
            background-color: var(--whatsapp-background);
            font-family: 'Nunito', sans-serif, Arial;
            display: flex;
            flex-direction: column;
            box-sizing: border-box; /* Include padding/border in element's total width and height */
        }

        #app {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            width: 100%; /* Ensure #app takes full width */
        }

        .content-section {
            flex-grow: 1; /* Permet à la section de contenu de prendre la hauteur disponible */
            overflow-y: auto; /* Active le défilement dans cette section */
            width: 100%; /* Explicitly set width to 100% */
            max-width: 800px; /* Still limit for large screens */
            margin: 0 auto; /* Center it */
            padding-top: 15px !important; /* Ajuste le rembourrage si la barre de navigation est fixe */
            padding-bottom: 80px; /* Espace pour le bouton flottant */
            background-color: var(--whatsapp-background); /* Assure un arrière-plan cohérent */
            box-sizing: border-box; /* Include padding in element's total width and height */
        }

        .whatsapp-heading {
            color: var(--whatsapp-green-dark);
            font-weight: 700;
            display: flex;
            align-items: center;
            margin-bottom: 20px !important; /* Plus d'espace sous le titre */
        }

        /* Styles de la barre de recherche WhatsApp (identiques aux listes pour la cohérence) */
        .whatsapp-search-form {
            border-radius: 20px; /* Très arrondi */
            overflow: hidden; /* Assure que le contenu respecte le border-radius */
            background-color: var(--whatsapp-search-bg);
            border: 1px solid var(--whatsapp-search-border);
            margin-bottom: 20px; /* Espace sous la barre de recherche */
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); /* Ombre subtile */
        }

        .whatsapp-search-input {
            background-color: transparent; /* Pas d'arrière-plan pour l'input lui-même */
            border: none;
            box-shadow: none !important; /* Supprime l'ombre au focus */
            padding: 0.5rem 1rem;
            color: var(--whatsapp-text-dark);
            border-radius: 20px 0 0 20px; /* Seul le côté gauche est arrondi */
        }

        .whatsapp-search-input::placeholder {
            color: var(--whatsapp-text-muted);
            opacity: 0.7;
        }

        .whatsapp-search-input:focus {
            border-color: transparent; /* Pas de bordure au focus */
            box-shadow: none; /* Pas d'ombre au focus */
        }

        .whatsapp-search-btn {
            background-color: transparent; /* Pas d'arrière-plan pour le bouton lui-même */
            border: none;
            color: var(--whatsapp-icon-color);
            padding: 0.5rem 1rem;
            border-radius: 0 20px 20px 0; /* Seul le côté droit est arrondi */
            transition: color 0.2s ease;
        }

        .whatsapp-search-btn:hover {
            color: var(--whatsapp-green-dark); /* Vert plus foncé au survol */
        }

        .conversations-list {
            /* background-color: var(--whatsapp-background); */ /* Supprimé car les éléments ont leur propre arrière-plan */
            border-radius: 8px;
            overflow: hidden;
            margin-top: 10px; /* Léger espace après la barre de recherche */
        }

        .conversation-item {
            background-color: var(--whatsapp-card-bg); /* Couleur d'arrière-plan de l'élément de conversation */
            border-bottom: 1px solid var(--whatsapp-border); /* Séparateur léger */
            transition: background-color 0.2s ease, transform 0.1s ease;
            cursor: pointer;
            color: inherit; /* Assure que le texte hérite de la couleur par défaut */
            border-radius: 10px; /* Coins plus doux */
            margin-bottom: 8px; /* Espace entre les éléments */
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); /* Ombre subtile */
            padding: 12px 15px !important; /* Plus de rembourrage */
            display: flex; /* Ensure it's a flex container */
            align-items: center; /* Align items vertically */
        }

        .conversation-item:last-child {
            border-bottom: none; /* Pas de bordure sur le dernier élément */
        }

        .conversation-item:hover {
            background-color: var(--whatsapp-light-hover); /* Couleur au survol */
            transform: translateY(-1px);
        }

        .conversation-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        /* Image d'avatar pour la liste de discussion */
        .avatar-thumbnail-chat-list {
            width: 56px; /* Plus grand pour la liste de conversation */
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--whatsapp-green-light); /* Bordure verte pour l'image */
        }

        /* Texte d'avatar pour la liste de discussion */
        .avatar-text-placeholder-chat-list {
            width: 56px; /* Plus grand pour la liste de conversation */
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem; /* Taille de police pour les initiales */
            font-weight: bold;
            color: white;
            border: 2px solid var(--whatsapp-green-light); /* Bordure verte pour l'avatar texte */
            text-transform: uppercase;
            background-color: #777; /* Repli si avatar_bg_color non défini */
        }

        .avatar-text-placeholder-chat-list.group-avatar { /* Style spécifique pour l'avatar de groupe */
            background-color: var(--whatsapp-green-dark) !important; /* Couleur spécifique pour les groupes */
            font-size: 1.5rem;
        }

        .conversation-info {
            flex-grow: 1;
            overflow: hidden; /* Important for containing children that might overflow */
            min-width: 0; /* Crucial: Allow flex item to shrink below its content size */
        }

        .conversation-name {
            font-weight: 600;
            color: var(--whatsapp-text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-grow: 1; /* Allow it to take available space */
            min-width: 0; /* Crucial: Allow flex item to shrink */
            font-size: 1.05rem; /* Nom légèrement plus grand */
        }

        .conversation-time {
            font-size: 0.75rem; /* Légèrement plus petit */
            color: var(--whatsapp-text-muted);
            flex-shrink: 0; /* Prevent shrinking */
        }

        .conversation-last-message {
            font-size: 0.88rem; /* Légèrement plus petit pour le dernier message */
            color: var(--whatsapp-text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%; /* S'assurer qu'il ne déborde pas du flex-grow-1 parent */
            display: flex; /* Permet d'aligner l'icône "Vous:" */
            align-items: center;
            min-width: 0; /* Crucial: Allow flex item to shrink */
        }

        .conversation-last-message .fa-check-double {
            color: var(--whatsapp-blue-seen); /* Couleur pour le statut lu */
            font-size: 0.7rem; /* Coche plus petite */
        }

        /* Nouveaux styles pour le badge de nombre de non lus */
        .unread-count-badge {
            background-color: var(--whatsapp-unread-badge); /* Vert WhatsApp pour les nouveaux messages */
            color: white;
            font-size: 0.75rem; /* Texte du badge plus petit */
            padding: 0.15em 0.4em; /* Rembourrage plus petit pour être plus compact */
            line-height: 1;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            font-weight: bold;
            min-width: 18px; /* Ensure minimum width for single digits, slightly smaller */
            height: 1.4em; /* Fixed height for consistent vertical alignment, slightly smaller */
            text-align: center; /* Center text within the badge */
            display: inline-flex; /* Use flex to center content */
            align-items: center;
            justify-content: center;
            box-sizing: border-box; /* Ensure padding doesn't add to total size unexpectedly */
        }

        /* Rendre le dernier message en gras s'il n'est pas lu */
        .conversation-last-message.fw-bold {
            font-weight: bold !important;
            color: var(--whatsapp-text-dark) !important;
        }

        /* Bouton flottant (décommenter si utilisé) */
        .floating-action-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            /* Pour centrer sur les petits écrans */
            left: auto; /* Réinitialiser la gauche */
            transform: none; /* Réinitialiser la transformation */
        }

        .floating-action-button .btn-whatsapp-send { /* Utiliser la même classe de bouton que le chat */
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background-color: var(--whatsapp-green-dark);
            border-color: var(--whatsapp-green-dark);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2); /* Ombre plus prononcée */
        }

        .floating-action-button .btn-whatsapp-send:hover {
            background-color: var(--whatsapp-green-light);
            border-color: var(--whatsapp-green-light);
        }

        /* Styles spécifiques à la modale */
        .modal-header.bg-whatsapp-green-dark {
            background-color: var(--whatsapp-green-dark) !important;
            color: white; /* Ensure text is white */
        }
        .modal-header .btn-close-white {
            filter: invert(1); /* Makes the close button white */
        }
        .modal-body .list-group-item:hover {
            background-color: var(--whatsapp-light-hover);
            cursor: pointer;
        }
        .user-avatar-modal {
            width: 45px; /* Légèrement plus grand dans la modale */
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px; /* Plus d'espace */
            flex-shrink: 0;
            border: 1px solid #ccc;
        }
        .user-initials-modal {
            width: 45px; /* Légèrement plus grand dans la modale */
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem; /* Taille de police ajustée */
            font-weight: bold;
            color: white;
            background-color: #777;
            margin-right: 12px; /* Plus d'espace */
            flex-shrink: 0;
        }
        .modal-dialog-scrollable .modal-body {
            max-height: calc(100vh - 200px); /* Ajuster en fonction de la taille de l'en-tête/pied de page */
            overflow-y: auto;
        }
        .alert-info.whatsapp-card { /* Style pour l'alerte d'état vide (si nécessaire, cohérent avec les listes) */
            background-color: var(--whatsapp-card-bg);
            border-color: var(--whatsapp-border);
            color: var(--whatsapp-text-dark);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Ombre cohérente */
        }


        /* Ajustements responsifs */
        @media (max-width: 576px) {
            .content-section {
                padding: 10px;
                padding-bottom: 70px; /* Ajuster pour un bouton flottant plus petit */
            }

            .avatar-thumbnail-chat-list, .avatar-text-placeholder-chat-list {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }

            .avatar-text-placeholder-chat-list.group-avatar {
                font-size: 1.4rem;
            }

            .conversation-name {
                font-size: 0.95rem;
            }

            .conversation-last-message {
                font-size: 0.85rem;
            }

            .conversation-time, .unread-count-badge {
                font-size: 0.7rem;
            }

            .floating-action-button {
                bottom: 15px;
                right: 50%; /* Centrer horizontalement */
                transform: translateX(50%); /* Ajuster pour le centrage */
            }

            .floating-action-button .btn-whatsapp-send {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }

            .user-avatar-modal, .user-initials-modal {
                width: 40px;
                height: 40px;
                font-size: 1rem;
                margin-right: 10px;
            }
        }
    </style>
@endpush

@push('scripts')
<script>
    // Fonction pour afficher une alerte personnalisée
    function showCustomAlert(message, type = 'danger') {
        const alertDiv = document.getElementById('customAlert');
        const alertMessageSpan = document.getElementById('customAlertMessage');
        alertMessageSpan.textContent = message;
        alertDiv.className = `alert alert-${type} fixed-top text-center`; // Réinitialise les classes
        alertDiv.style.display = 'block';
        setTimeout(() => {
            alertDiv.style.display = 'none';
        }, 5000); // Cache après 5 secondes
    }

    document.addEventListener('DOMContentLoaded', function() {
        const newChatModal = document.getElementById('newChatModal');
        const userSearchInput = document.getElementById('userSearchInput');
        const userListContainer = document.getElementById('userList');
        let searchTimeout;

        // Fonction pour récupérer et afficher les utilisateurs
        async function fetchUsers(query = '') {
            userListContainer.innerHTML = '<p class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i> Chargement...</p>';

            const searchUrl = window.Laravel && window.Laravel.routes && window.Laravel.routes.chatsSearchUsers
                                    ? `${window.Laravel.routes.chatsSearchUsers}?query=${encodeURIComponent(query)}`
                                    : `/chats/search-users?query=${encodeURIComponent(query)}`;

            try {
                const response = await fetch(searchUrl);
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Erreur HTTP ! statut : ${response.status}. Réponse: ${errorText}`);
                }
                const data = await response.json();

                // Gérer la structure de la réponse (peut être un tableau direct ou un objet avec une propriété 'users')
                const users = Array.isArray(data) ? data : (data.users || []);

                userListContainer.innerHTML = ''; // Efface le message de chargement

                if (users.length > 0) {
                    users.forEach(user => {
                        let avatarHtml;
                        const avatarPath = user.profile_picture;
                        const isExternalAvatar = avatarPath && (avatarPath.startsWith('http://') || avatarPath.startsWith('https://'));

                        if (avatarPath) {
                            const avatarSrc = isExternalAvatar ? avatarPath : `/storage/${avatarPath}`;
                            avatarHtml = `<img src="${avatarSrc}" alt="Avatar" class="user-avatar-modal" onerror="this.onerror=null;this.src='https://placehold.co/45x45/ccc/white?text=?';">`;
                        } else {
                            // Utilise les propriétés initials et avatar_bg_color du JSON de l'utilisateur
                            const initials = user.initials || (user.name ? user.name.split(' ').map(n => n[0]).join('').substring(0, 2) : '??').toUpperCase();
                            const bgColor = user.avatar_bg_color || '#777'; // Fallback si le backend ne le fournit pas
                            avatarHtml = `<div class="user-initials-modal" style="background-color: ${bgColor};">${initials}</div>`;
                        }

                        const listItem = `
                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center" data-user-id="${user.id}">
                                ${avatarHtml}
                                <span class="fw-bold">${user.name}</span>
                                <small class="text-muted ms-auto">(${user.user_type === 'employer' ? 'Employeur' : 'Candidat'})</small>
                            </a>
                        `;
                        userListContainer.insertAdjacentHTML('beforeend', listItem);
                    });

                    // Ajouter un écouteur de clic à chaque élément utilisateur après qu'ils aient été ajoutés au DOM
                    userListContainer.querySelectorAll('.list-group-item').forEach(item => {
                        item.addEventListener('click', async function(e) {
                            e.preventDefault();
                            const userId = this.dataset.userId;
                            if (userId) {
                                const createChatUrl = '/chats/create'; // URL directe ou utiliser une route de window.Laravel.routes si définie

                                try {
                                    const response = await fetch(createChatUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({ recipient_id: userId })
                                    });

                                    const data = await response.json();

                                    if (data.success && (data.conversation_id || data.redirect_to_existing_chat)) {
                                        window.location.href = data.redirect_to_existing_chat || `/chats/${data.conversation_id}`;
                                    } else {
                                        console.error(data.message || 'Erreur lors de la création de la discussion.');
                                        showCustomAlert(data.message || 'Erreur lors de la création de la discussion.', 'danger');
                                    }
                                } catch (error) {
                                    console.error('Erreur lors de la création de la discussion :', error);
                                    showCustomAlert('Une erreur est survenue lors de la création de la discussion.', 'danger');
                                }
                            }
                        });
                    });
                } else {
                    userListContainer.innerHTML = '<p class="text-center text-muted py-3">Aucun utilisateur trouvé.</p>';
                }
            } catch (error) {
                console.error('Erreur lors de la récupération des utilisateurs :', error);
                showCustomAlert('Erreur lors du chargement des utilisateurs.', 'danger');
                userListContainer.innerHTML = '<p class="text-center text-danger py-3">Erreur lors du chargement des utilisateurs.</p>';
            }
        }

        // Écouteur d'événement pour le champ de recherche avec debounce
        userSearchInput.addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            const query = this.value;
            searchTimeout = setTimeout(() => {
                if (query.length >= 2 || query.length === 0) { // Récupérer si 2+ caractères ou vide pour tout afficher
                    fetchUsers(query);
                } else if (query.length < 2) {
                    userListContainer.innerHTML = '<p class="text-center text-muted py-3">Tapez au moins 2 caractères pour rechercher des utilisateurs.</p>';
                }
            }, 300); // Debounce de 300ms
        });

        // Écouteur d'événement pour l'affichage de la modale
        newChatModal.addEventListener('show.bs.modal', function () {
            userSearchInput.value = ''; // Effacer le champ de recherche
            userListContainer.innerHTML = '<p class="text-center text-muted py-3">Tapez pour rechercher des utilisateurs...</p>'; // Réinitialiser le message
            fetchUsers(''); // Charger tous les utilisateurs lorsque la modale s'ouvre (ou les 20/50 premiers, etc.)
        });
    });
</script>
@endpush
