<?php
// Les déclarations 'use' doivent être au tout début du fichier Blade,
// avant tout contenu HTML ou autres directives Blade complexes.
use Illuminate\Support\Str;
?>
@extends('layouts.user') {{-- Assurez-vous que c'est bien votre layout 'user' --}}

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
                                $displayAvatarHtml = '<div class="avatar-text-placeholder-small" style="background-color: ' . $bgColor . ';">' . $initials . '</div>';
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
            {{-- Icônes d'action dans le header (appel vidéo, appel audio, menu) --}}
            <div class="chat-actions">
                <a href="#" class="icon-button"><i class="fas fa-video"></i></a>
                <a href="#" class="icon-button"><i class="fas fa-phone-alt"></i></a>
                <a href="#" class="icon-button"><i class="fas fa-ellipsis-v"></i></a>
            </div>
        </div>

        {{-- Zone des messages --}}
        <div class="chat-messages p-3" id="chatMessages">
            @forelse ($messages as $message)
                @php
                    $messageSender = $message->user;
                    $messageSenderAvatarHtml = '';
                    // Générer une couleur de fond aléatoire basée sur l'ID de l'utilisateur pour une couleur constante
                    $senderBgColor = '#' . substr(md5($messageSender->email ?? $messageSender->id ?? uniqid()), 0, 6);


                    $isSenderExternalAvatar = $messageSender->profile_picture && Str::startsWith($messageSender->profile_picture, ['http://', 'https://']);

                    if ($messageSender->profile_picture) {
                        $messageSenderAvatarSrc = $isSenderExternalAvatar ? $messageSender->profile_picture : asset('storage/' . $messageSender->profile_picture);
                        $messageSenderAvatarHtml = '<img src="' . $messageSenderAvatarSrc . '" alt="Photo de profil de ' . $messageSender->name . '" class="message-sender-avatar">';
                    } else {
                        $initials = '';
                        if ($messageSender->name) {
                            $words = explode(' ', $messageSender->name);
                            foreach ($words as $word) {
                                $initials .= strtoupper(substr($word, 0, 1));
                            }
                            if (strlen($initials) > 2) {
                                $initials = substr($initials, 0, 2);
                            }
                        } else {
                            $initials = '??';
                        }
                        $messageSenderAvatarHtml = '<div class="message-sender-avatar-placeholder" style="background-color: ' . $senderBgColor . ';">' . $initials . '</div>';
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
                            <div class="message-sender-name" style="color: {{ $senderBgColor }};">
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
                    <i class="fas fa-comments fa-3x mb-3"></i>
                    <p>Commencez la discussion en envoyant votre premier message !</p>
                </div>
            @endforelse
        </div>

        {{-- Formulaire d'envoi de message --}}
        <div class="chat-input-area p-3">
            <form id="messageForm" action="{{ route('chats.sendMessage', $conversation->id) }}" method="POST" class="d-flex align-items-end"> {{-- Changed align-items-center to align-items-end --}}
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

                // Récupérer les données de l'utilisateur authentifié pour l'affichage de l'avatar et couleur nom
                const authUser = @json(Auth::user());
                let authUserAvatarHtml = '';
                let authUserColor = '#' + CryptoJS.MD5(authUser.email || authUser.id || new Date().getTime().toString()).toString().substring(0, 6);

                if (authUser.profile_picture) {
                    const isExternal = authUser.profile_picture.startsWith('http://') || authUser.profile_picture.startsWith('https://');
                    const avatarSrc = isExternal ? authUser.profile_picture : "{{ asset('storage/') }}" + '/' + authUser.profile_picture;
                    authUserAvatarHtml = `<img src="${avatarSrc}" alt="Photo de profil" class="message-sender-avatar">`;
                } else {
                    let initials = '';
                    if (authUser.name) {
                        const words = authUser.name.split(' ');
                        words.forEach(word => {
                            initials += word.substring(0, 1).toUpperCase();
                        });
                        if (initials.length > 2) {
                            initials = initials.substring(0, 2);
                        }
                    } else {
                        initials = '??';
                    }
                    authUserAvatarHtml = `<div class="message-sender-avatar-placeholder" style="background-color: ${authUserColor};">${initials}</div>`;
                }

                const messageTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const isGroup = {{ $conversation->is_group ? 'true' : 'false' }};

                let newMessageHtml = `
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

                // If it's a group and the message is sent by the current user, you might still want to show their name/avatar
                // but WhatsApp usually doesn't show the sender's avatar for their own sent messages.
                // However, if you want to explicitly show the sender's name in the bubble for groups:
                if (isGroup && response.data.message.user_id === authUser.id) {
                     // This part is for messages *received* from others in a group, not sent by self.
                     // The `sent` bubble generally doesn't show the sender's name/avatar within itself in WhatsApp.
                     // The PHP loop already handles displaying name/avatar for *received* group messages.
                     // No change needed here for sent messages from current user.
                }


                chatMessagesDiv.insertAdjacentHTML('beforeend', newMessageHtml);

                // Nettoyer le textarea
                textarea.value = '';
                textarea.style.height = 'auto'; // Réinitialiser la hauteur

                // Faire défiler vers le bas pour afficher le nouveau message
                scrollToBottom();
            })
            .catch(error => {
                console.error('Erreur d\'envoi du message:', error);
                // Afficher un message d'erreur à l'utilisateur si l'envoi échoue
                alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
            });
    });

    // Inclure la bibliothèque CryptoJS pour la génération de couleurs (si non déjà présente)
    // C'est un polyfill pour le MD5 que j'ai utilisé en JS pour être cohérent avec PHP
    // Si tu utilises déjà une autre méthode pour générer des couleurs, tu peux l'adapter.
    if (typeof CryptoJS === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js';
        document.head.appendChild(script);
    }
</script>
@endpush

@push('styles')
<style>
    /* Variables WhatsApp */
    :root {
        --whatsapp-green-dark: #075E54; /* En-tête, boutons d'envoi */
        --whatsapp-green-light: #128C7E; /* Accent, hover */
        --whatsapp-blue-seen: #34B7F1; /* Double coche bleue */
        --whatsapp-background: #E5DDD5; /* Fond principal de la page (hors chat) */
        --whatsapp-chat-bg: url('https://user-images.githubusercontent.com/15075759/28719142-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); /* Fond du corps de la discussion - image WhatsApp */
        --whatsapp-message-sent: #DCF8C6; /* Couleur bulle message envoyé */
        --whatsapp-message-received: #FFFFFF; /* Couleur bulle message reçu */
        --whatsapp-text-dark: #202C33; /* Texte plus sombre pour lisibilité */
        --whatsapp-text-muted: #667781; /* Gris pour horodatages et icônes non actives */
        --whatsapp-border: #DDD; /* Bordures légères */
        --whatsapp-input-bg: #F0F0F0; /* Fond du champ de saisie */
    }

    /* Styles généraux pour le corps et l'application */
    html, body {
        height: 100%; /* S'assure que HTML et BODY prennent toute la hauteur */
        margin: 0;
        padding: 0;
        overflow: hidden; /* Empêche le défilement général du document */
    }

    body {
        background-color: var(--whatsapp-background); /* Fond général, sera supplanté par chat-container si large */
        font-family: 'Nunito', sans-serif, Arial; /* Police WhatsApp-like */
        display: flex;
        flex-direction: column;
    }

    #app {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        overflow: hidden; /* Empêche #app de déborder */
    }

    .chat-container {
        display: flex;
        flex-direction: column;
        height: 100%; /* Prend toute la hauteur disponible de #app */
        max-width: 800px; /* Limite la largeur sur grand écran pour ressembler à l'interface web WhatsApp */
        margin: 0 auto; /* Centre le conteneur sur grand écran */
        background-color: #ECE5DD; /* Couleur de fond par défaut pour le chat-container */
        overflow: hidden; /* Important pour que le chat-messages défile à l'intérieur */
        box-shadow: 0 0 10px rgba(0,0,0,0.1); /* Ombre douce sur les côtés sur desktop */
    }

    /* En-tête de la discussion */
    .chat-header {
        background-color: var(--whatsapp-green-dark);
        color: white;
        flex-shrink: 0; /* Empêche l'en-tête de se rétrécir */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        padding: 10px 15px; /* Ajuste le padding */
        display: flex;
        align-items: center;
    }

    .chat-header .back-button {
        color: white;
        font-size: 1.5rem;
        text-decoration: none;
        margin-right: 15px; /* Espacement avec l'avatar */
        transition: opacity 0.2s;
    }
    .chat-header .back-button:hover {
        opacity: 0.8;
    }

    .chat-header .chat-avatar-wrapper {
        position: relative;
        flex-shrink: 0;
    }

    /* Avatars dans le header */
    .avatar-chat-header,
    .avatar-text-placeholder-small,
    .avatar-group-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        /* border: 2px solid var(--whatsapp-green-light); /* Bordure verte - souvent absente dans le header de WhatsApp */
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        text-transform: uppercase;
        flex-shrink: 0;
        font-size: 1.1rem;
    }
    .avatar-text-placeholder-small {
        background-color: #888; /* Fallback */
    }
    .avatar-group-placeholder {
        font-size: 1.5rem;
        background-color: var(--whatsapp-green-light); /* Couleur par défaut pour les groupes */
    }

    .chat-header .chat-title {
        flex-grow: 1;
        margin-left: 10px; /* Espacement entre avatar et titre */
    }
    .chat-header .chat-title h5 {
        font-weight: 600;
        margin-bottom: 2px;
        font-size: 1.1rem;
    }
    .chat-header .chat-title .participants-list {
        font-size: 0.8rem;
        opacity: 0.9;
        color: rgba(255, 255, 255, 0.8); /* Texte plus clair pour les participants */
    }

    .chat-header .chat-actions {
        display: flex;
        gap: 20px; /* Espacement entre les icônes d'action */
        margin-left: auto;
    }
    .chat-header .chat-actions .icon-button {
        color: white;
        font-size: 1.3rem;
        text-decoration: none;
        transition: opacity 0.2s;
    }
    .chat-header .chat-actions .icon-button:hover {
        opacity: 0.8;
    }

    /* Zone des messages */
    .chat-messages {
        flex-grow: 1;
        overflow-y: auto;
        padding: 10px 15px; /* Ajuste le padding des messages */
        background-image: var(--whatsapp-chat-bg); /* Fond d'écran WhatsApp */
        background-size: cover;
        background-position: center;
        background-attachment: local; /* change fixed to local so background scrolls with content */
        display: flex;
        flex-direction: column; /* Pour empiler les bulles */
        scroll-behavior: smooth; /* Défilement doux */
    }

    /* Bulles de message */
    .message-bubble {
        display: flex;
        margin-bottom: 6px; /* Espacement plus petit entre les messages */
        align-items: flex-end; /* Aligner les avatars et les bulles en bas */
        position: relative; /* Pour les pseudo-éléments des pointes */
        max-width: 85%; /* Plus de place pour le contenu */
    }

    /* Avatar du l'expéditeur dans les messages de groupe */
    .message-sender-avatar-container {
        flex-shrink: 0;
        width: 30px;
        height: 30px;
        position: relative;
        margin-right: 8px;
        align-self: flex-start; /* Aligner l'avatar en haut du bloc */
    }

    .message-sender-avatar,
    .message-sender-avatar-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        /* border: 1px solid rgba(0, 0, 0, 0.1); */ /* Souvent pas de bordure sur les avatars de message */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
        color: white;
        text-transform: uppercase;
        background-color: #777; /* Fallback */
    }

    .message-bubble .message-content {
        /* max-width: 75%; */ /* max-width est sur .message-bubble */
        padding: 8px 12px;
        border-radius: 8px; /* Rayon standard des bulles WhatsApp */
        position: relative;
        word-wrap: break-word;
        font-size: 0.95rem;
        line-height: 1.4;
        color: var(--whatsapp-text-dark);
        box-shadow: 0 1px 0.5px rgba(0, 0, 0, 0.13); /* Ombre douce de WhatsApp */
        display: flex; /* Permet d'aligner le texte et l'heure */
        flex-direction: column;
    }

    /* Pointe des bulles (pseudo-éléments) */
    .message-bubble.sent .message-content::before {
        content: '';
        position: absolute;
        top: 0;
        right: -10px; /* Positionnement de la pointe */
        border: 6px solid transparent;
        border-top-color: var(--whatsapp-message-sent);
        border-right-color: var(--whatsapp-message-sent);
        /* rotate: 20deg; */ /* Légère rotation pour la pointe WhatsApp */
    }
    .message-bubble.received .message-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: -10px; /* Positionnement de la pointe */
        border: 6px solid transparent;
        border-top-color: var(--whatsapp-message-received);
        border-left-color: var(--whatsapp-message-received);
        /* rotate: -20deg; */ /* Légère rotation pour la pointe WhatsApp */
    }

    .message-bubble.sent {
        align-self: flex-end; /* Pousse la bulle à droite dans le flex-column */
        background-color: var(--whatsapp-message-sent); /* Pour la bulle entière */
        border-top-right-radius: 0; /* Pour la pointe */
        border-bottom-right-radius: 8px; /* Conserver le coin */
    }

    .message-bubble.sent .message-content {
        background-color: var(--whatsapp-message-sent); /* Assure la couleur de la bulle */
        border-top-right-radius: 0; /* Pointe sur le coin supérieur droit */
        border-bottom-right-radius: 8px;
    }

    .message-bubble.received {
        align-self: flex-start; /* Pousse la bulle à gauche dans le flex-column */
        background-color: var(--whatsapp-message-received); /* Pour la bulle entière */
        border: 1px solid rgba(0, 0, 0, 0.05); /* Légère bordure pour le reçu */
        border-top-left-radius: 0; /* Pour la pointe */
        border-bottom-left-radius: 8px; /* Conserver le coin */
    }

    .message-bubble.received .message-content {
        background-color: var(--whatsapp-message-received); /* Assure la couleur de la bulle */
        border-top-left-radius: 0; /* Pointe sur le coin supérieur gauche */
        border-bottom-left-radius: 8px;
    }


    .message-time {
        font-size: 0.7rem;
        color: var(--whatsapp-text-muted);
        text-align: right;
        display: block;
        margin-top: 5px;
        margin-left: auto; /* Pousse l'heure à droite dans la bulle */
        white-space: nowrap;
        padding-left: 10px; /* Espacement entre le texte et l'heure/coche */
    }
    .message-bubble.received .message-time {
        margin-left: 0; /* Pas de margin-left auto pour le reçu */
        margin-right: auto; /* Pousse l'heure à gauche pour le reçu */
    }

    /* Icônes de lecture (double-coche) */
    .fa-check-double {
        color: var(--whatsapp-text-muted); /* Non lue (gris) */
        font-size: 0.65rem; /* Taille de l'icône */
    }
    .fa-check-double.text-whatsapp-blue-seen {
        color: var(--whatsapp-blue-seen); /* Vue (bleu) */
    }

    .message-sender-name {
        font-weight: bold;
        font-size: 0.8rem; /* Plus petit pour le nom de l'expéditeur */
        margin-bottom: 4px;
        /* La couleur est définie inline dans le blade pour être dynamique */
    }

    /* Zone de saisie du message */
    .chat-input-area {
        background-color: var(--whatsapp-input-bg);
        /* border-top: 1px solid var(--whatsapp-border); */ /* Souvent pas de bordure supérieure sur l'input area */
        flex-shrink: 0;
        padding: 10px 15px;
        display: flex;
        align-items: flex-end; /* Aligne le bouton et le textearea au bas */
    }

    .chat-textarea {
        border-radius: 20px; /* Très arrondi */
        padding: 10px 15px;
        border: none; /* Pas de bordure par défaut */
        background-color: #FFF;
        resize: none;
        min-height: 40px;
        max-height: 120px; /* Augmenter un peu la hauteur max */
        overflow-y: auto;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); /* Ombre douce */
        transition: all 0.2s ease;
        flex-grow: 1;
        font-size: 0.95rem;
        line-height: 1.4;
    }

    .chat-textarea:focus {
        box-shadow: 0 0 0 0.25rem rgba(18, 140, 126, 0.25);
        /* border-color: var(--whatsapp-green-light); */ /* Pas de bordure visible au focus sur WhatsApp, juste l'ombre */
        outline: none; /* Supprime l'outline par défaut du navigateur */
    }

    .btn-whatsapp-send {
        background-color: var(--whatsapp-green-dark);
        color: white;
        width: 48px;
        height: 48px;
        min-width: 48px;
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        transition: background-color 0.2s ease, transform 0.1s ease;
        margin-left: 8px;
    }

    .btn-whatsapp-send:hover {
        background-color: var(--whatsapp-green-light);
        color: white;
        transform: scale(1.05); /* Léger effet de zoom au survol */
    }

    /* Styles pour la scrollbar (Webkit) */
    .chat-messages::-webkit-scrollbar,
    .chat-textarea::-webkit-scrollbar {
        width: 6px;
        background: transparent;
    }

    .chat-messages::-webkit-scrollbar-thumb,
    .chat-textarea::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.2);
        border-radius: 10px;
    }

    /* Alertes (pour "Commencez la discussion") */
    #noMessagesAlert {
        margin-top: 20px;
        background-color: rgba(255, 255, 255, 0.8);
        border: none;
        border-radius: 8px;
        padding: 20px;
        color: var(--whatsapp-text-dark);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    #noMessagesAlert .fas {
        color: var(--whatsapp-green-light);
        margin-bottom: 15px;
    }


    /* Sur les petits écrans (mobiles) */
    @media (max-width: 768px) {
        .chat-container {
            border-radius: 0;
            box-shadow: none;
        }
        .chat-header {
            padding: 10px 15px; /* Moins de padding sur mobile */
        }
        .chat-header .back-button {
            font-size: 1.3rem;
            margin-right: 10px;
        }
        .avatar-chat-header,
        .avatar-text-placeholder-small,
        .avatar-group-placeholder {
            width: 40px; /* Plus petit sur mobile */
            height: 40px;
            font-size: 1rem;
        }
        .avatar-group-placeholder {
            font-size: 1.3rem;
        }
        .chat-header .chat-title h5 {
            font-size: 1rem;
        }
        .chat-header .chat-title .participants-list {
            font-size: 0.75rem;
        }
        .chat-header .chat-actions .icon-button {
            font-size: 1.1rem;
            gap: 15px;
        }
        .message-bubble {
            max-width: 90%; /* Occupe plus de largeur sur mobile */
        }
        .message-bubble .message-content {
            font-size: 0.9rem;
            padding: 7px 10px;
        }
        .message-time {
            font-size: 0.65rem;
        }
        .chat-input-area {
            padding: 8px 12px;
        }
        .chat-textarea {
            min-height: 38px;
            max-height: 90px;
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        .btn-whatsapp-send {
            width: 42px;
            height: 42px;
            min-width: 42px;
            min-height: 42px;
            font-size: 1.1rem;
            margin-left: 6px;
        }
    }
</style>
@endpush