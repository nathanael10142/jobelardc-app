@extends('layouts.user')

@section('title', 'Discussions - Jobela RDC')

@section('content')
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

        {{-- Zone de recherche WhatsApp pour les conversations --}}
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
                                // Find the other participant in a 1-on-1 chat
                                $otherParticipant = $conversation->users->first(fn($u) => $u->id !== Auth::id());

                                if ($otherParticipant) {
                                    $displayName = $otherParticipant->name;
                                    $avatarPath = $otherParticipant->profile_picture ?? null;
                                    $isExternalAvatar = $avatarPath && \Illuminate\Support\Str::startsWith($avatarPath, ['http://', 'https://']);

                                    if ($avatarPath) {
                                        $avatarSrc = $isExternalAvatar ? $avatarPath : asset('storage/' . $avatarPath);
                                        $displayAvatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil de ' . $otherParticipant->name . '" class="avatar-thumbnail-chat-list">';
                                    } else {
                                        // Fallback to initials avatar if no profile picture
                                        // Ensure User model has getInitialsAttribute and getAvatarBgColorAttribute
                                        $displayAvatarHtml = '<div class="avatar-text-placeholder-chat-list" style="background-color: ' . ($otherParticipant->avatar_bg_color ?? '#777') . ';">' . ($otherParticipant->initials ?? '??') . '</div>';
                                    }
                                } else {
                                    // Fallback for unexpected scenario (e.g., chat with self, or user deleted)
                                    $displayName = 'Utilisateur inconnu';
                                    $displayAvatarHtml = '<div class="avatar-text-placeholder-chat-list" style="background-color: #777;">??</div>';
                                }
                            }
                        @endphp
                        {!! $displayAvatarHtml !!}
                    </div>
                    <div class="conversation-info flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 conversation-name">{{ $displayName }}</h6>
                            <div class="d-flex align-items-center">
                                @if ($conversation->lastMessage)
                                    <small class="text-muted conversation-time">{{ $conversation->lastMessage->created_at->diffForHumans() }}</small>
                                @endif

                                {{-- Unread messages count badge --}}
                                @if ($conversation->unread_messages_count > 0)
                                    <span class="badge rounded-pill unread-count-badge ms-2">
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

        {{-- Floating button for starting a new chat --}}
        <div class="floating-action-button">
            <button type="button" class="btn btn-whatsapp-send rounded-circle shadow-lg" title="Nouvelle Discussion" data-bs-toggle="modal" data-bs-target="#newChatModal">
                <i class="fas fa-plus fa-lg"></i>
            </button>
        </div>
    </div>

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
            --whatsapp-background: #E5DDD5; /* Fond principal de la page */
            --whatsapp-card-bg: #FFFFFF; /* Fond des cartes de conversation */
            --whatsapp-light-hover: #F0F0F0; /* Couleur de survol */
            --whatsapp-text-dark: #202C33; /* Texte plus sombre pour lisibilité */
            --whatsapp-text-muted: #667781; /* Gris pour horodatages et messages lus */
            --whatsapp-border: #E0E0E0; /* Bordure des cartes */
            --whatsapp-unread-badge: #25D366; /* Couleur du badge non lu */
            --whatsapp-search-bg: #F0F2F5; /* Background for search input */
            --whatsapp-search-border: #D1D7DA; /* Border for search input */
            --whatsapp-icon-color: #667781; /* Color for search icon */
        }

        body {
            background-color: var(--whatsapp-background);
            font-family: Arial, sans-serif; /* Police WhatsApp-like */
        }

        .content-section {
            max-width: 800px; /* Limite la largeur pour une meilleure lisibilité */
            margin: 0 auto; /* Centre le contenu */
            padding-top: 15px !important; /* Ajuste le padding si la navbar est fixe */
        }

        .whatsapp-heading {
            color: var(--whatsapp-green-dark);
            font-weight: 700;
            display: flex;
            align-items: center;
            margin-bottom: 20px !important; /* Plus d'espace sous le titre */
        }

        /* WhatsApp Search Bar Styles (same as listings for consistency) */
        .whatsapp-search-form {
            border-radius: 20px; /* Highly rounded */
            overflow: hidden; /* Ensure content respects border-radius */
            background-color: var(--whatsapp-search-bg);
            border: 1px solid var(--whatsapp-search-border);
            margin-bottom: 20px; /* Space below the search bar */
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

        .conversations-list {
            background-color: var(--whatsapp-background); /* Fond de la liste */
            border-radius: 8px;
            overflow: hidden;
        }

        .conversation-item {
            background-color: var(--whatsapp-card-bg); /* Couleur de fond des éléments de conversation */
            border-bottom: 1px solid var(--whatsapp-border); /* Séparateur léger */
            transition: background-color 0.2s ease, transform 0.1s ease;
            cursor: pointer;
            color: inherit; /* S'assure que le texte hérite de la couleur par défaut */
        }

        .conversation-item:last-child {
            border-bottom: none; /* Pas de bordure sur le dernier élément */
        }

        .conversation-item:hover {
            background-color: var(--whatsapp-light-hover); /* Couleur de survol */
            transform: translateY(-1px);
        }

        .conversation-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        /* Avatar image for chat list */
        .avatar-thumbnail-chat-list {
            width: 58px; /* Plus grand pour la liste de conversations */
            height: 58px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--whatsapp-green-light); /* Bordure verte pour l'image */
        }

        /* Avatar text for chat list */
        .avatar-text-placeholder-chat-list {
            width: 58px; /* Plus grand pour la liste de conversations */
            height: 58px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem; /* Taille de police pour les initiales */
            font-weight: bold;
            color: white;
            border: 2px solid var(--whatsapp-green-light); /* Bordure verte pour l'avatar texte */
            text-transform: uppercase;
            background-color: #777; /* Fallback si avatar_bg_color non défini */
        }

        .avatar-text-placeholder-chat-list.group-avatar { /* Style spécifique pour l'avatar de groupe */
            background-color: var(--whatsapp-green-dark) !important; /* Couleur spécifique pour les groupes */
            font-size: 1.6rem;
        }

        .conversation-info {
            overflow: hidden;
        }

        .conversation-name {
            font-weight: 600;
            color: var(--whatsapp-text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: calc(100% - 70px); /* Ajuster selon la taille de l'heure/badge */
        }

        .conversation-time {
            font-size: 0.78rem;
            color: var(--whatsapp-text-muted);
            flex-shrink: 0;
            margin-left: 10px;
        }

        .conversation-last-message {
            font-size: 0.9rem;
            color: var(--whatsapp-text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 95%; /* S'assurer qu'il ne déborde pas */
            display: flex; /* Permet d'aligner l'icône "Vous:" */
            align-items: center;
        }

        .conversation-last-message .fa-check-double {
            color: var(--whatsapp-blue-seen); /* Couleur pour le statut lu */
        }

        /* New styles for unread count badge */
        .unread-count-badge {
            background-color: var(--whatsapp-unread-badge); /* Vert WhatsApp pour les nouveaux messages */
            color: white;
            font-size: 0.78rem;
            padding: 0.3em 0.6em;
            line-height: 1;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            font-weight: bold;
        }

        /* Make last message bold if unread */
        .conversation-last-message.fw-bold {
            font-weight: bold !important;
            color: var(--whatsapp-text-dark) !important;
        }

        /* Floating button (uncomment if used) */
        .floating-action-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .floating-action-button .btn-whatsapp-send { /* Utilisez la même classe de bouton que le chat */
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

        /* Modal specific styles */
        .modal-header.bg-whatsapp-green-dark {
            background-color: var(--whatsapp-green-dark) !important;
        }
        .modal-header .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .modal-body .list-group-item:hover {
            background-color: var(--whatsapp-light-hover);
            cursor: pointer;
        }
        .user-avatar-modal {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            flex-shrink: 0;
            border: 1px solid #ccc;
        }
        .user-initials-modal {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: bold;
            color: white;
            background-color: #777;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .modal-dialog-scrollable .modal-body {
            max-height: calc(100vh - 200px); /* Adjust based on header/footer size */
            overflow-y: auto;
        }
        .alert-info.whatsapp-card { /* Style for empty state alert (if needed, consistent with listings) */
            background-color: var(--whatsapp-card-bg);
            border-color: var(--whatsapp-border);
            color: var(--whatsapp-text-dark);
            border-radius: 12px;
            padding: 1.5rem;
        }


        /* Responsive adjustments */
        @media (max-width: 576px) {
            .content-section {
                padding: 10px;
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
                right: 15px;
            }

            .floating-action-button .btn-whatsapp-send {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
        }
    </style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const newChatModal = document.getElementById('newChatModal');
        const userSearchInput = document.getElementById('userSearchInput');
        const userListContainer = document.getElementById('userList');
        let searchTimeout;

        // Function to fetch and display users
        function fetchUsers(query = '') {
            userListContainer.innerHTML = '<p class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i> Chargement...</p>';

            fetch(`/chats/search-users?query=${query}`)
                .then(response => response.json())
                .then(data => {
                    userListContainer.innerHTML = '';
                    if (data.users && data.users.length > 0) {
                        data.users.forEach(user => {
                            let avatarHtml;
                            const avatarPath = user.profile_picture;
                            const isExternalAvatar = avatarPath && (avatarPath.startsWith('http://') || avatarPath.startsWith('https://'));

                            if (avatarPath) {
                                const avatarSrc = isExternalAvatar ? avatarPath : `/storage/${avatarPath}`;
                                avatarHtml = `<img src="${avatarSrc}" alt="Avatar" class="user-avatar-modal">`;
                            } else {
                                // Fallback to initials if no profile picture
                                // Assuming your User model has accessors for initials and avatar_bg_color
                                const initials = user.initials || (user.name ? user.name.split(' ').map(n => n[0]).join('') : '??').toUpperCase();
                                const bgColor = user.avatar_bg_color || '#777'; // Fallback color
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

                        // Add click listener to each user item
                        userListContainer.querySelectorAll('.list-group-item').forEach(item => {
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                const userId = this.dataset.userId;
                                if (userId) {
                                    // Create conversation via POST request
                                    fetch('/chats/create', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({ recipient_id: userId })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success && data.conversation_id) {
                                            window.location.href = `/chats/${data.conversation_id}`; // Redirect to chat show page
                                        } else if (data.redirect_to_existing_chat) {
                                            window.location.href = data.redirect_to_existing_chat; // Redirect to existing chat
                                        } else {
                                            alert(data.message || 'Erreur lors de la création de la discussion.');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        alert('Une erreur est survenue lors de la création de la discussion.');
                                    });
                                }
                            });
                        });
                    } else {
                        userListContainer.innerHTML = '<p class="text-center text-muted py-3">Aucun utilisateur trouvé.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching users:', error);
                    userListContainer.innerHTML = '<p class="text-center text-danger py-3">Erreur lors du chargement des utilisateurs.</p>';
                });
        }

        // Search input event listener with debounce
        userSearchInput.addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            const query = this.value;
            searchTimeout = setTimeout(() => {
                if (query.length >= 2 || query.length === 0) { // Fetch if 2+ chars or empty to show all
                    fetchUsers(query);
                } else if (query.length < 2 && userListContainer.innerHTML !== '<p class="text-center text-muted py-3">Tapez pour rechercher des utilisateurs...</p>') {
                    userListContainer.innerHTML = '<p class="text-center text-muted py-3">Tapez au moins 2 caractères pour rechercher des utilisateurs.</p>';
                }
            }, 300); // 300ms debounce
        });

        // Event listener for when the modal is shown
        newChatModal.addEventListener('show.bs.modal', function () {
            userSearchInput.value = ''; // Clear search input
            fetchUsers(''); // Load all users when modal opens (or first 20/50 etc.)
        });
    });
</script>
@endpush
