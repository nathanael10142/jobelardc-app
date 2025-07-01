@extends('layouts.user')

@section('title', 'Discussion avec ' . ($conversation->is_group ? ($conversation->name ?: 'Groupe de discussion') : ($conversation->users->first(fn($u) => $u->id !== Auth::id())->name ?? 'Utilisateur inconnu')) . ' - Jobela RDC')

@section('content')
    <div class="chat-container">
        {{-- En-tête de la discussion --}}
        <div class="chat-header p-3 d-flex align-items-center">
            <a href="{{ route('chats.index') }}" class="back-button me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="chat-avatar-wrapper me-3">
                @php
                    $displayAvatarHtml = '';
                    $displayName = '';
                    $isGroup = $conversation->is_group;

                    if ($isGroup) {
                        $displayName = $conversation->name ?: 'Groupe de discussion';
                        $displayAvatarHtml = '<div class="avatar-group-placeholder"><i class="fas fa-users"></i></div>';
                    } else {
                        // Trouver l'autre participant dans un chat 1-à-1
                        $otherParticipant = $conversation->users->first(fn($u) => $u->id !== Auth::id());

                        if ($otherParticipant) {
                            $displayName = $otherParticipant->name;
                            $avatarPath = $otherParticipant->profile_picture ?? null;
                            $isExternalAvatar = $avatarPath && Str::startsWith($avatarPath, ['http://', 'https://']);

                            if ($avatarPath) {
                                $avatarSrc = $isExternalAvatar ? $avatarPath : asset('storage/' . $avatarPath);
                                $displayAvatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil de ' . $otherParticipant->name . '" class="avatar-chat-header">';
                            } else {
                                // Fallback aux initiales si pas de photo de profil
                                // Assurez-vous que $otherParticipant->initials et $otherParticipant->avatar_bg_color sont définis dans votre modèle User
                                $displayAvatarHtml = '<div class="avatar-text-placeholder-small" style="background-color: ' . ($otherParticipant->avatar_bg_color ?? '#777') . ';">' . ($otherParticipant->initials ?? '??') . '</div>';
                            }
                        } else {
                            // Fallback pour un scénario inattendu (ex: chat avec soi-même, ou utilisateur supprimé)
                            $displayName = 'Utilisateur inconnu';
                            $displayAvatarHtml = '<div class="avatar-text-placeholder-small" style="background-color: #777;">??</div>';
                        }
                    }
                @endphp
                {!! $displayAvatarHtml !!}
            </div>
            <div class="chat-title flex-grow-1">
                <h5 class="mb-0">{{ $displayName }}</h5>
                {{-- Afficher les participants si c'est un groupe --}}
                @if($isGroup)
                    <small class="text-white-75 participants-list">
                        @foreach($conversation->users->take(3) as $user)
                            {{ $user->name }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                        @if($conversation->users->count() > 3)
                            ... ({{ $conversation->users->count() }} membres)
                        @endif
                    </small>
                @endif
            </div>
            {{-- Ajoutez des icônes pour les appels, etc., ici si vous les implémentez --}}
            {{-- <div class="chat-actions">
                <a href="#" class="text-white me-3"><i class="fas fa-video"></i></a>
                <a href="#" class="text-white"><i class="fas fa-phone-alt"></i></a>
            </div> --}}
        </div>

        {{-- Zone des messages --}}
        <div class="chat-messages p-3" id="chatMessages">
            @forelse ($messages as $message)
                @php
                    $messageSender = $message->user;
                    $messageSenderAvatarHtml = '';
                    $isSenderExternalAvatar = $messageSender->profile_picture && Str::startsWith($messageSender->profile_picture, ['http://', 'https://']);

                    if ($messageSender->profile_picture) {
                        $messageSenderAvatarSrc = $isSenderExternalAvatar ? $messageSender->profile_picture : asset('storage/' . $messageSender->profile_picture);
                        $messageSenderAvatarHtml = '<img src="' . $messageSenderAvatarSrc . '" alt="Photo de profil de ' . $messageSender->name . '" class="message-sender-avatar">';
                    } else {
                        // Assurez-vous que $messageSender->initials et $messageSender->avatar_bg_color sont définis dans votre modèle User
                        $messageSenderAvatarHtml = '<div class="message-sender-avatar-placeholder" style="background-color: ' . ($messageSender->avatar_bg_color ?? '#777') . ';">' . ($messageSender->initials ?? '??') . '</div>';
                    }
                @endphp

                <div class="message-bubble {{ $message->user_id === Auth::id() ? 'sent' : 'received' }}">
                    @if($conversation->is_group && $message->user_id !== Auth::id())
                        <div class="message-sender-avatar-container me-2">
                            {!! $messageSenderAvatarHtml !!}
                        </div>
                    @endif
                    <div class="message-content">
                        @if($conversation->is_group && $message->user_id !== Auth::id())
                            <div class="message-sender-name" style="color: {{ $message->user->avatar_bg_color ?? '#000' }};">
                                {{ $message->user->name }}
                            </div>
                        @endif
                        <p class="mb-0">{{ $message->body }}</p>
                        <small class="message-time">
                            {{ $message->created_at->format('H:i') }}
                            @if($message->user_id === Auth::id())
                                <i class="fas fa-check-double ms-1 {{ $message->read_at ? 'text-whatsapp-blue-seen' : 'text-muted' }}"></i>
                            @endif
                        </small>
                    </div>
                </div>
            @empty
                <div class="alert alert-info text-center" id="noMessagesAlert">
                    Commencez la discussion en envoyant votre premier message !
                </div>
            @endforelse
        </div>

        {{-- Formulaire d'envoi de message --}}
        <div class="chat-input-area p-3">
            <form id="messageForm" action="{{ route('chats.messages.store', $conversation->id) }}" method="POST" class="d-flex align-items-center">
                @csrf
                <textarea name="body" class="form-control me-2 chat-textarea" placeholder="Tapez votre message..." rows="1" required></textarea>
                <button type="submit" class="btn btn-whatsapp-send rounded-circle p-2">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // Faire défiler la zone de messages vers le bas au chargement et après l'envoi
    function scrollToBottom() {
        var chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    document.addEventListener('DOMContentLoaded', scrollToBottom);

    // Auto-redimensionnement du textarea
    document.addEventListener('input', function (event) {
        if (event.target.classList.contains('chat-textarea')) {
            event.target.style.height = 'auto';
            event.target.style.height = (event.target.scrollHeight) + 'px';
        }
    });

    // Gérer l'envoi de message via AJAX pour une expérience plus fluide
    document.getElementById('messageForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Empêche le rechargement de la page par le formulaire
        
        let form = this;
        let textarea = form.querySelector('textarea[name="body"]');
        let messageBody = textarea.value.trim();

        if (messageBody === '') {
            return; // N'envoie pas de message vide
        }

        axios.post(form.action, { body: messageBody })
            .then(response => {
                // Créer dynamiquement la bulle de message envoyée
                const chatMessagesDiv = document.getElementById('chatMessages');
                const noMessagesAlert = document.getElementById('noMessagesAlert');

                // Supprime l'alerte "Commencez la discussion" si elle existe
                if (noMessagesAlert) {
                    noMessagesAlert.remove();
                }

                // Récupérer les données de l'utilisateur authentifié pour l'affichage de l'avatar
                // Vous devrez passer ces données depuis votre contrôleur si elles ne sont pas globales
                // Pour l'exemple, je vais simuler avec des données basiques
                const authUserId = {{ Auth::id() }};
                const authUserName = "{{ Auth::user()->name }}";
                const authUserAvatar = "{{ Auth::user()->profile_picture ? asset('storage/' . Auth::user()->profile_picture) : '' }}";
                const authUserInitials = "{{ Auth::user()->initials ?? '??' }}";
                const authUserAvatarBgColor = "{{ Auth::user()->avatar_bg_color ?? '#777' }}";

                // Construire l'HTML de l'avatar de l'expéditeur
                let senderAvatarHtml = '';
                if (authUserAvatar) {
                    senderAvatarHtml = `<img src="${authUserAvatar}" alt="Photo de profil de ${authUserName}" class="message-sender-avatar">`;
                } else {
                    senderAvatarHtml = `<div class="message-sender-avatar-placeholder" style="background-color: ${authUserAvatarBgColor};">${authUserInitials}</div>`;
                }

                const messageTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                const newMessageHtml = `
                    <div class="message-bubble sent">
                        <div class="message-content">
                            <p class="mb-0">${messageBody}</p>
                            <small class="message-time">
                                ${messageTime}
                                <i class="fas fa-check-double ms-1 text-muted"></i>
                            </small>
                        </div>
                    </div>
                `;
                chatMessagesDiv.insertAdjacentHTML('beforeend', newMessageHtml);

                // Nettoyer le textarea
                textarea.value = '';
                textarea.style.height = 'auto'; // Réinitialiser la hauteur

                // Faire défiler vers le bas pour afficher le nouveau message
                scrollToBottom();

                // Optionnel: Mettre à jour l'icône de double-coche en "vu" si le message est marqué comme lu côté serveur
                // Cela nécessiterait une réponse JSON du serveur qui indique l'état de lecture ou un événement broadcast
            })
            .catch(error => {
                console.error('Erreur d\'envoi du message:', error);
                // Afficher un message d'erreur à l'utilisateur si l'envoi échoue
                alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
            });
    });
</script>
@endpush

@push('styles')
<style>
    /* Variables WhatsApp (assurez-vous qu'elles sont définies globalement ou ici) */
    :root {
        --whatsapp-green-dark: #075E54;
        --whatsapp-green-light: #128C7E;
        --whatsapp-blue-seen: #34B7F1;
        --whatsapp-background: #E5DDD5; /* Fond principal de la page */
        --whatsapp-chat-bg: #ECE5DD; /* Fond du corps de la discussion */
        --whatsapp-message-sent: #DCF8C6; /* Couleur bulle message envoyé */
        --whatsapp-message-received: #FFFFFF; /* Couleur bulle message reçu */
        --whatsapp-text-dark: #202C33; /* Texte plus sombre pour lisibilité */
        --whatsapp-text-muted: #667781; /* Gris pour horodatages */
        --whatsapp-border: #DDD;
        --whatsapp-input-bg: #F0F0F0; /* Fond du champ de saisie */
    }

    body {
        background-color: var(--whatsapp-chat-bg); /* Fond spécifique au chat */
        margin: 0; /* Assurez-vous qu'il n'y a pas de marge par défaut du body */
        font-family: Arial, sans-serif; /* Police WhatsApp-like */
    }

    .chat-container {
        display: flex;
        flex-direction: column;
        height: 100vh; /* Utilise 100% de la hauteur de la fenêtre, y compris la navbar si elle est fixe */
        max-width: 800px; /* Limite la largeur pour une meilleure lisibilité sur grand écran */
        margin: 0 auto; /* Centre le conteneur */
        background-color: var(--whatsapp-chat-bg);
        /* Suppression des border-radius et box-shadow pour un look plein écran sur mobile,
           mais vous pouvez les réactiver pour un effet "carte" sur desktop */
        /* border-radius: 8px; */
        /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); */
        overflow: hidden; /* Pour éviter les problèmes de débordement */
    }

    /* En-tête de la discussion */
    .chat-header {
        background-color: var(--whatsapp-green-dark);
        color: white;
        flex-shrink: 0; /* Empêche l'en-tête de se rétrécir */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Légère ombre pour le faire ressortir */
    }

    .chat-header .back-button {
        color: white;
        font-size: 1.5rem; /* Icône plus grande */
        text-decoration: none;
    }

    .chat-header .chat-avatar-wrapper {
        position: relative;
    }

    /* Avatars dans le header */
    .avatar-chat-header {
        width: 48px; /* Taille légèrement plus grande pour l'avatar dans le header */
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--whatsapp-green-light);
    }

    .avatar-text-placeholder-small { /* Utilisé pour les initiales */
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem; /* Taille de police légèrement plus grande */
        font-weight: bold;
        color: white;
        border: 2px solid var(--whatsapp-green-light);
        text-transform: uppercase;
        background-color: #888; /* Fallback si avatar_bg_color non défini */
    }

    .avatar-group-placeholder { /* Avatar spécifique aux groupes */
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem; /* Icône plus grande */
        font-weight: bold;
        color: white;
        background-color: var(--whatsapp-green-light); /* Couleur par défaut pour les groupes */
        border: 2px solid var(--whatsapp-green-light);
    }

    .chat-header .chat-title h5 {
        font-weight: 600;
        margin-bottom: 2px; /* Espacement réduit */
    }

    .chat-header .chat-title .participants-list {
        font-size: 0.8rem; /* Plus petite pour la liste des participants */
        opacity: 0.8;
    }

    /* Zone des messages */
    .chat-messages {
        flex-grow: 1; /* Permet à la zone de messages de prendre l'espace disponible */
        overflow-y: auto; /* Active le défilement vertical si le contenu dépasse */
        padding: 10px;
        background-image: url('{{ asset('images/whatsapp-chat-bg.png') }}'); /* Fond d'écran WhatsApp */
        background-size: cover;
        background-position: center;
        background-attachment: fixed; /* Pour que l'arrière-plan ne défile pas avec les messages */
    }

    /* Bulles de message */
    .message-bubble {
        display: flex;
        margin-bottom: 8px;
        align-items: flex-end; /* Aligner les avatars et les bulles en bas */
    }

    /* Avatar du l'expéditeur dans les messages de groupe */
    .message-sender-avatar-container {
        flex-shrink: 0; /* Empêche l'avatar de rétrécir */
        width: 30px; /* Taille pour l'avatar dans les messages */
        height: 30px;
        position: relative;
        margin-right: 8px; /* Espacement entre l'avatar et la bulle */
    }

    .message-sender-avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid rgba(0, 0, 0, 0.1); /* Petite bordure pour la définition */
    }

    .message-sender-avatar-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
        color: white;
        text-transform: uppercase;
        background-color: #777; /* Fallback couleur si non définie */
    }

    .message-bubble .message-content {
        max-width: 75%; /* Limite la largeur des bulles */
        padding: 8px 12px;
        border-radius: 10px;
        position: relative;
        word-wrap: break-word; /* Casse les mots longs */
        font-size: 0.95rem;
        line-height: 1.4;
        color: var(--whatsapp-text-dark); /* Couleur du texte dans les bulles */
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.08); /* Légère ombre pour les bulles */
    }

    .message-bubble.sent {
        justify-content: flex-end; /* Alignement à droite pour les messages envoyés */
    }

    .message-bubble.sent .message-content {
        background-color: var(--whatsapp-message-sent);
        margin-left: auto; /* Pousse la bulle à droite */
        border-bottom-right-radius: 2px; /* Pour la pointe du message, comme WhatsApp */
    }

    .message-bubble.received {
        justify-content: flex-start; /* Alignement à gauche pour les messages reçus */
    }

    .message-bubble.received .message-content {
        background-color: var(--whatsapp-message-received);
        margin-right: auto; /* Pousse la bulle à gauche */
        border: 1px solid rgba(0, 0, 0, 0.05); /* Bordure légère pour les messages reçus */
        border-bottom-left-radius: 2px; /* Pour la pointe du message, comme WhatsApp */
    }

    .message-time {
        font-size: 0.7rem;
        color: var(--whatsapp-text-muted);
        text-align: right;
        display: block;
        margin-top: 5px;
        white-space: nowrap; /* Empêche l'heure de se casser sur plusieurs lignes */
    }

    /* Icônes de lecture (double-coche) */
    .fa-check-double {
        color: var(--whatsapp-text-muted); /* Non lue (gris) */
    }

    .fa-check-double.text-whatsapp-blue-seen {
        color: var(--whatsapp-blue-seen); /* Vue (bleu) */
    }

    .message-sender-name {
        font-weight: bold;
        font-size: 0.85rem;
        margin-bottom: 2px;
    }

    /* Zone de saisie du message */
    .chat-input-area {
        background-color: var(--whatsapp-input-bg);
        border-top: 1px solid var(--whatsapp-border);
        flex-shrink: 0;
        padding: 10px 15px; /* Ajuste le padding */
        display: flex;
        align-items: flex-end; /* Aligne le bouton et le textearea au bas */
    }

    .chat-textarea {
        border-radius: 20px;
        padding: 10px 15px;
        border: none;
        background-color: #FFF;
        resize: none; /* Empêche le redimensionnement manuel */
        min-height: 40px; /* Hauteur minimale */
        max-height: 100px; /* Hauteur maximale pour éviter un champ trop grand */
        overflow-y: auto; /* Activer le défilement si le texte dépasse max-height */
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
        flex-grow: 1; /* Permet au textarea de prendre l'espace disponible */
        font-size: 0.95rem;
    }

    .chat-textarea:focus {
        box-shadow: 0 0 0 0.25rem rgba(18, 140, 126, 0.25); /* Ombre de focus verte */
        border-color: var(--whatsapp-green-light);
    }

    .btn-whatsapp-send {
        background-color: var(--whatsapp-green-dark);
        color: white;
        width: 48px; /* Taille légèrement plus grande pour le bouton */
        height: 48px;
        min-width: 48px; /* S'assure qu'il garde sa taille */
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem; /* Icône plus grande */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        transition: background-color 0.2s ease;
        margin-left: 8px; /* Espacement entre le textarea et le bouton */
    }

    .btn-whatsapp-send:hover {
        background-color: var(--whatsapp-green-light);
        color: white;
    }

    /* Styles pour la scrollbar (Webkit) */
    .chat-messages::-webkit-scrollbar,
    .chat-textarea::-webkit-scrollbar {
        width: 6px; /* Légèrement plus large pour être visible */
        background: transparent;
    }

    .chat-messages::-webkit-scrollbar-thumb,
    .chat-textarea::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.2); /* Moins opaque */
        border-radius: 10px;
    }

    /* Sur les petits écrans (mobiles) */
    @media (max-width: 768px) {
        .chat-container {
            border-radius: 0;
            box-shadow: none;
        }
    }
</style>
@endpush
