    <?php use Illuminate\Support\Str; ?>
    @extends('layouts.user')

    @section('title', 'Mes Discussions - Jobela RDC')

    @section('content')
        {{-- ALERTE PERSONNALISÉE : Assurez-vous que ce bloc est présent dans layouts/user.blade.php ou ici --}}
        <div id="customAlert" class="alert alert-danger fixed-top text-center" style="display:none; z-index:9999; margin-top: 20px;">
            <span id="customAlertMessage"></span>
            <button type="button" class="btn-close" onclick="document.getElementById('customAlert').style.display='none';" aria-label="Close"></button>
        </div>
        {{-- FIN ALERTE PERSONNALISÉE --}}

        <div class="content-section p-3" id="main-chats-content">
            <h5 class="mb-3 whatsapp-heading">
                <i class="fas fa-comments me-2"></i> Mes Discussions
            </h5>

            <form id="chatSearchForm" class="whatsapp-search-form flex-grow-1 me-3">
                <div class="input-group">
                    <input type="text" name="search" id="chatSearchInput" class="form-control whatsapp-search-input" placeholder="{{ __('Rechercher discussions ou contacts...') }}" value="{{ request('search') }}">
                    <button class="btn whatsapp-search-btn" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <div class="d-flex justify-content-end align-items-center mb-3">
                <a href="#" class="btn btn-whatsapp-primary rounded-pill px-4 shadow-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#createConversationModal">
                    <i class="fas fa-plus me-2"></i> Nouvelle Discussion
                </a>
            </div>

            <div class="chats-container" id="chatsList">
                @forelse ($conversations as $conversation)
                    @php
                        $displayAvatarHtml = '';
                        $displayName = '';
                        $lastMessageBody = $conversation->lastMessage ? $conversation->lastMessage->body : 'Aucun message';
                        $lastMessageTime = $conversation->lastMessage ? $conversation->lastMessage->created_at->format('H:i') : '';
                        $unreadCount = $conversation->unread_messages_count ?? 0;

                        if ($conversation->is_group) {
                            $displayName = $conversation->name ?: 'Groupe de discussion';
                            $displayAvatarHtml = '<div class="avatar-group-placeholder"><i class="fas fa-users"></i></div>';
                        } else {
                            $otherParticipant = $conversation->users->first(fn($u) => $u->id !== Auth::id());

                            if ($otherParticipant) {
                                $displayName = $otherParticipant->name;
                                $avatarPath = $otherParticipant->profile_picture ?? null;
                                $isExternalAvatar = $avatarPath && Str::startsWith($avatarPath, ['http://', 'https://']);

                                if ($avatarPath) {
                                    $avatarSrc = $isExternalAvatar ? $avatarPath : asset('storage/' . $avatarPath);
                                    $displayAvatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil de ' . $otherParticipant->name . '" class="avatar-thumbnail">';
                                } else {
                                    $initials = '';
                                    if ($otherParticipant->name) {
                                        $words = explode(' ', $otherParticipant->name);
                                        foreach ($words as $word) {
                                            $initials .= strtoupper(substr($word, 0, 1));
                                        }
                                        if (strlen($initials) > 2) {
                                            $initials = substr($initials, 0, 2);
                                        }
                                    } else {
                                        $initials = '??';
                                    }
                                    $bgColor = '#' . substr(md5($otherParticipant->email ?? $otherParticipant->id ?? uniqid()), 0, 6);
                                    $displayAvatarHtml = '<div class="avatar-text-placeholder" style="background-color: ' . $bgColor . ';">' . $initials . '</div>';
                                }
                            } else {
                                $displayName = 'Utilisateur inconnu';
                                $displayAvatarHtml = '<div class="avatar-text-placeholder" style="background-color: #777;">??</div>';
                            }
                        }
                    @endphp
                    <a href="{{ route('chats.show', $conversation->id) }}" class="card chat-card mb-3 shadow-sm">
                        <div class="card-body p-3 d-flex align-items-center">
                            {!! $displayAvatarHtml !!}
                            <div class="flex-grow-1 ms-3 overflow-hidden">
                                <h6 class="profile-name mb-0">{{ $displayName }}</h6>
                                <p class="last-message text-muted mb-0 text-truncate">{{ $lastMessageBody }}</p>
                            </div>
                            <div class="ms-auto text-end flex-shrink-0">
                                <small class="message-time d-block">{{ $lastMessageTime }}</small>
                                @if ($unreadCount > 0)
                                    <span class="badge bg-success rounded-pill mt-1">{{ $unreadCount }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="alert alert-info text-center whatsapp-card" role="alert">
                        <i class="fas fa-info-circle me-2"></i> Aucune discussion trouvée.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Modal pour créer une nouvelle conversation --}}
        <div class="modal fade" id="createConversationModal" tabindex="-1" aria-labelledby="createConversationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content whatsapp-card">
                    <div class="modal-header whatsapp-heading-modal">
                        <h5 class="modal-title" id="createConversationModalLabel">Nouvelle Discussion</h5>
                        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createConversationForm">
                            <div class="mb-3">
                                <label for="userSearchInput" class="form-label">Rechercher un utilisateur:</label>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control whatsapp-search-input" id="userSearchInput" placeholder="Nom ou email...">
                                    <button class="btn whatsapp-search-btn" type="button" id="clearUserSearchButton"><i class="fas fa-times"></i></button>
                                </div>
                                <div class="list-group" id="userListForConversation">
                                    <p class="text-muted text-center p-2">Commencez à taper pour rechercher des utilisateurs...</p>
                                </div>
                                <input type="hidden" name="recipient_id" id="selectedUserId">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-whatsapp-primary" id="startNewChatButton" disabled><i class="fas fa-comment-dots me-2"></i> Démarrer la Discussion</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    @endsection

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        function showCustomAlert(message, type = 'info') {
            const alertDiv = document.getElementById('customAlert');
            const alertMessageSpan = document.getElementById('customAlertMessage');
            if (alertDiv && alertMessageSpan) {
                alertMessageSpan.textContent = message;
                alertDiv.className = `alert alert-${type} fixed-top text-center`;
                alertDiv.style.display = 'block';
                setTimeout(() => {
                    alertDiv.style.display = 'none';
                }, 5000);
            } else {
                console.warn('Custom alert element not found. Displaying standard alert:', message);
                alert(message);
            }
        }
        // Rendre la fonction accessible globalement via l'objet window
        window.showCustomAlert = showCustomAlert;


        function md5(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash |= 0;
            }
            return (hash >>> 0).toString(16).padStart(8, '0');
        }

        function getInitials(name) {
            if (!name) return '?';
            const parts = name.split(' ');
            if (parts.length > 1) {
                return (parts[0][0] + parts[1][0]).toUpperCase();
            }
            return name[0].toUpperCase();
        }

        const userSearchInput = document.getElementById('userSearchInput');
        const userListForConversation = document.getElementById('userListForConversation');
        const selectedUserIdInput = document.getElementById('selectedUserId');
        const startNewChatButton = document.getElementById('startNewChatButton');
        const createConversationForm = document.getElementById('createConversationForm');
        const clearUserSearchButton = document.getElementById('clearUserSearchButton');
        const chatSearchInput = document.getElementById('chatSearchInput');
        const chatsList = document.getElementById('chatsList');


        const debounce = (func, delay) => {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), delay);
            };
        };

        const fetchUsersForNewConversation = debounce(async (query) => {
            if (query.length < 2) {
                userListForConversation.innerHTML = '<p class="text-muted text-center p-2">Commencez à taper pour rechercher des utilisateurs...</p>';
                startNewChatButton.disabled = true;
                selectedUserIdInput.value = '';
                return;
            }

            try {
                const response = await axios.get(`{{ route('chats.searchUsers') }}?query=${query}`);
                const users = response.data;
                renderUserListForConversation(users);
            } catch (error) {
                console.error('Erreur lors de la recherche d\'utilisateurs:', error);
                userListForConversation.innerHTML = '<p class="text-danger text-center p-2">Erreur de chargement des utilisateurs.</p>';
            }
        }, 300);

        function renderUserListForConversation(users) {
            userListForConversation.innerHTML = '';
            if (users.length === 0) {
                userListForConversation.innerHTML = '<p class="text-muted text-center p-2">Aucun utilisateur trouvé.</p>';
                return;
            }

            users.forEach(user => {
                const avatarHtml = user.profile_picture
                    ? `<img src="${user.profile_picture}" alt="${user.name}" class="avatar-thumbnail me-3" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">`
                    : `<div class="avatar-text-placeholder bg-primary me-3" style="width: 40px; height: 40px; font-size: 1.2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: ${user.avatar_bg_color || '#ccc'};">${user.initials}</div>`;

                const listItem = document.createElement('a');
                listItem.href = "#";
                listItem.className = 'list-group-item list-group-item-action d-flex align-items-center';
                listItem.dataset.userId = user.id;
                listItem.innerHTML = `
                    ${avatarHtml}
                    <span class="user-name">${user.name}</span>
                `;
                listItem.addEventListener('click', (e) => {
                    e.preventDefault();
                    document.querySelectorAll('#userListForConversation .list-group-item').forEach(item => item.classList.remove('active'));
                    listItem.classList.add('active');
                    selectedUserIdInput.value = user.id;
                    startNewChatButton.disabled = false;
                    userSearchInput.value = user.name; // Remplir l'input avec le nom sélectionné
                    userListForConversation.innerHTML = ''; // Cacher la liste après sélection
                });
                userListForConversation.appendChild(listItem);
            });
        }

        createConversationForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const recipientId = selectedUserIdInput.value;

            if (!recipientId) {
                showCustomAlert('Veuillez sélectionner un utilisateur.', 'warning');
                return;
            }

            try {
                const response = await axios.post(`{{ route('chats.createConversation') }}`, {
                    recipient_id: recipientId
                });

                if (response.data.success) {
                    showCustomAlert(response.data.message, 'success');
                    if (response.data.redirect_to_existing_chat) {
                        window.location.href = response.data.redirect_to_existing_chat;
                    }
                } else {
                    showCustomAlert(response.data.message, 'danger');
                }
            } catch (error) {
                console.error('Erreur lors de la création de la discussion:', error);
                showCustomAlert('Erreur lors de la création de la discussion. Veuillez réessayer.', 'danger');
            }
        });

        userSearchInput.addEventListener('input', (e) => fetchUsersForNewConversation(e.target.value));

        clearUserSearchButton.addEventListener('click', () => {
            userSearchInput.value = '';
            userListForConversation.innerHTML = '<p class="text-muted text-center p-2">Commencez à taper pour rechercher des utilisateurs...</p>';
            selectedUserIdInput.value = '';
            startNewChatButton.disabled = true;
        });

        // Handle search on chat index page
        chatSearchInput.addEventListener('input', debounce(async (e) => {
            const searchQuery = e.target.value;
            try {
                const response = await axios.get(`{{ route('chats.index') }}?search=${searchQuery}`);
                const parser = new DOMParser();
                const doc = parser.parseFromString(response.data, 'text/html');
                const newChatsListContent = doc.getElementById('chatsList').innerHTML;
                chatsList.innerHTML = newChatsListContent;
            } catch (error) {
                console.error('Erreur lors de la recherche de discussions:', error);
                showCustomAlert('Erreur lors de la recherche de discussions.', 'danger');
            }
        }, 300));

    </script>
    @endpush

    @push('styles')
    {{-- AJOUT ICI : Charge chat.css spécifiquement pour cette page via Vite --}}
    @vite('resources/css/chat.css')
    @endpush
    