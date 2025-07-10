<?php
use Illuminate\Support\Str;
?>
@extends('layouts.user')

@section('title', 'Discussion avec ' . ($conversation->is_group ? ($conversation->name ?: 'Groupe de discussion') : ($conversation->users->first(fn($u) => $u->id !== Auth::id())->name ?? 'Utilisateur inconnu')) . ' - Jobela RDC')

@section('content')
    <div class="chat-container">
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
                        $otherParticipant = $conversation->users->first(fn($u) => $u->id !== Auth::id());

                        if ($otherParticipant) {
                            $displayName = $otherParticipant->name;
                            $avatarPath = $otherParticipant->profile_picture ?? null;
                            $isExternalAvatar = $avatarPath && Str::startsWith($avatarPath, ['http://', 'https://']);

                            if ($avatarPath) {
                                $avatarSrc = $isExternalAvatar ? $avatarPath : asset('storage/' . $avatarPath);
                                $displayAvatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil de ' . $otherParticipant->name . '" class="avatar-chat-header">';
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
                                $displayAvatarHtml = '<div class="avatar-text-placeholder-small" style="background-color: ' . $bgColor . ';">' . $initials . '</div>';
                            }
                        } else {
                            $displayName = 'Utilisateur inconnu';
                            $displayAvatarHtml = '<div class="avatar-text-placeholder-small" style="background-color: #777;">??</div>';
                        }
                    }
                @endphp
                {!! $displayAvatarHtml !!}
            </div>
            <div class="chat-title flex-grow-1">
                <h5 class="mb-0">{{ $displayName }}</h5>
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
            <div class="chat-actions">
                <a href="#" class="icon-button"><i class="fas fa-video"></i></a>
                <a href="#" class="icon-button"><i class="fas fa-phone-alt"></i></a>
                <a href="#" class="icon-button"><i class="fas fa-ellipsis-v"></i></a>
            </div>
        </div>

        <div class="chat-messages p-3" id="chatMessages">
            @forelse ($messages as $message)
                @php
                    $messageSender = $message->user;
                    $messageSenderAvatarHtml = '';
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

                <div class="message-bubble {{ $message->user_id === Auth::id() ? 'sent' : 'received' }}" data-message-id="{{ $message->id }}">
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
                                <i class="fas fa-check-double ms-1 {{ $message->read_at ? 'text-whatsapp-blue-seen' : 'text-muted' }}" data-read-receipt="{{ $message->id }}"></i>
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

        <div class="chat-input-area p-3">
            <form id="messageForm" action="{{ route('chats.sendMessage', $conversation->id) }}" method="POST" class="d-flex align-items-center">
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
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.0.1/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.0/dist/echo.iife.js"></script>

<script>
    // Initialisation de Pusher et Echo directement dans ce script
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: '{{ config('broadcasting.connections.pusher.key') }}',
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
        forceTLS: true,
        authEndpoint: '/broadcasting/auth',
        authorizer: (channel, options) => {
            return {
                authorize: (socketId, callback) => {
                    axios.post('/broadcasting/auth', {
                        socket_id: socketId,
                        channel_name: channel.name
                    }, {
                        withCredentials: true
                    })
                    .then(response => callback(null, response.data))
                    .catch(error => {
                        console.error('Erreur d\'autorisation Broadcasting:', error);
                        callback(new Error('Échec de l\'authentification Broadcasting.'), null);
                    });
                }
            };
        },
    });

    function md5(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash |= 0;
        }
        return (hash >>> 0).toString(16).padStart(8, '0');
    }

    function getAvatarHtml(user, isGroup, size = '30px', fontSize = '0.8rem') {
        let avatarHtml = '';
        if (isGroup) {
            avatarHtml = `<div class="message-sender-avatar-placeholder" style="background-color: #777;"><i class="fas fa-users"></i></div>`;
        } else if (user && user.profile_picture) {
            const isExternal = user.profile_picture.startsWith('http://') || user.profile_picture.startsWith('https://');
            const avatarSrc = isExternal ? user.profile_picture : "{{ asset('storage/') }}" + '/' + user.profile_picture;
            avatarHtml = `<img src="${avatarSrc}" alt="Photo de profil" class="message-sender-avatar">`;
        } else if (user) {
            let initials = '';
            if (user.name) {
                const words = user.name.split(' ');
                words.forEach(word => {
                    initials += word.substring(0, 1).toUpperCase();
                });
                if (initials.length > 2) {
                    initials = initials.substring(0, 2);
                }
            } else {
                initials = '??';
            }
            const bgColor = '#' + md5(user.email || user.id || 'default').substring(0, 6);
            avatarHtml = `<div class="message-sender-avatar-placeholder" style="background-color: ${bgColor};">${initials}</div>`;
        } else {
            avatarHtml = `<div class="message-sender-avatar-placeholder" style="background-color: #999;"><i class="fas fa-user-circle"></i></div>`;
        }
        return avatarHtml;
    }

    function scrollToBottom() {
        var chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    document.addEventListener('DOMContentLoaded', scrollToBottom);

    document.addEventListener('input', function (event) {
        if (event.target.classList.contains('chat-textarea')) {
            event.target.style.height = 'auto';
            event.target.style.height = (event.target.scrollHeight) + 'px';
        }
    });

    document.getElementById('messageForm').addEventListener('submit', function(e) {
        e.preventDefault();

        let form = this;
        let textarea = form.querySelector('textarea[name="body"]');
        let messageBody = textarea.value.trim();

        if (messageBody === '') {
            return;
        }

        const messageTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const tempMessageId = 'temp-' + Date.now();

        const chatMessagesDiv = document.getElementById('chatMessages');
        const noMessagesAlert = document.getElementById('noMessagesAlert');

        if (noMessagesAlert) {
            noMessagesAlert.remove();
        }

        let newMessageHtml = `
            <div class="message-bubble sent" data-message-id="${tempMessageId}">
                <div class="message-content">
                    <p class="mb-0">${messageBody}</p>
                    <small class="message-time">
                        ${messageTime}
                        <i class="fas fa-check-double ms-1 text-muted" data-read-receipt="${tempMessageId}"></i>
                    </small>
                </div>
            </div>
        `;

        chatMessagesDiv.insertAdjacentHTML('beforeend', newMessageHtml);
        scrollToBottom();

        textarea.value = '';
        textarea.style.height = 'auto';

        axios.post(form.action, { body: messageBody })
            .then(response => {
                const realMessageId = response.data.message.id;
                const tempBubble = document.querySelector(`[data-message-id="${tempMessageId}"]`);
                if (tempBubble) {
                    tempBubble.setAttribute('data-message-id', realMessageId);
                    const readReceiptIcon = tempBubble.querySelector('[data-read-receipt]');
                    if (readReceiptIcon) {
                        readReceiptIcon.setAttribute('data-read-receipt', realMessageId);
                    }
                }
                console.log('Message envoyé avec succès et ID réel mis à jour:', realMessageId);
            })
            .catch(error => {
                console.error('Erreur d\'envoi du message:', error);
                alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
                const tempBubble = document.querySelector(`[data-message-id="${tempMessageId}"]`);
                if (tempBubble) {
                    tempBubble.remove();
                }
            });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const conversationId = {{ $conversation->id }};
        const currentUserId = {{ Auth::id() }};
        const chatMessagesDiv = document.getElementById('chatMessages');
        const noMessagesAlert = document.getElementById('noMessagesAlert');
        const isGroupConversation = {{ $conversation->is_group ? 'true' : 'false' }};

        // Demander la permission de notification
        if (Notification.permission === "default") {
            Notification.requestPermission();
        }

        window.Echo.private(`conversations.${conversationId}`)
            .listen('MessageSent', (e) => {
                console.log('Nouveau message reçu:', e.message);

                if (e.message.user_id === currentUserId) {
                    console.log('Message auto-envoyé détecté. Sa visibilité est gérée par l\'envoi optimiste.');
                    return;
                }

                if (noMessagesAlert) {
                    noMessagesAlert.remove();
                }

                let senderAvatarHtml = '';
                let senderNameHtml = '';

                const sender = e.message.user;
                const senderColor = '#' + md5(sender.email || sender.id || 'default').substring(0, 6);

                senderAvatarHtml = getAvatarHtml(sender, isGroupConversation);

                if (isGroupConversation) {
                    senderNameHtml = `<div class="message-sender-name" style="color: ${senderColor};">${sender.name}</div>`;
                }

                const receivedTime = new Date(e.message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                let messageReceivedHtml = `
                    <div class="message-bubble received" data-message-id="${e.message.id}">
                        ${ isGroupConversation && e.message.user_id !== currentUserId ? `<div class="message-sender-avatar-container me-2">${senderAvatarHtml}</div>` : '' }
                        <div class="message-content">
                            ${ isGroupConversation && e.message.user_id !== currentUserId ? senderNameHtml : '' }
                            <p class="mb-0">${e.message.body}</p>
                            <small class="message-time">${receivedTime}</small>
                        </div>
                    </div>
                `;

                chatMessagesDiv.insertAdjacentHTML('beforeend', messageReceivedHtml);
                scrollToBottom();

                // Notification visuelle
                if (Notification.permission === "granted") {
                    new Notification(`Nouveau message de ${sender.name}`, {
                        body: e.message.body,
                        icon: sender.profile_picture ? (sender.profile_picture.startsWith('http') ? sender.profile_picture : "{{ asset('storage/') }}" + '/' + sender.profile_picture) : 'https://placehold.co/48x48/ccc/white?text=DM'
                    });
                }

                // Notification sonore
                const audio = new Audio('/audio/whatsapp_notification.mp3');
                audio.play().catch(e => console.error("Erreur lecture son de notification:", e));

                axios.post(`/messages/${e.message.id}/read`)
                    .then(response => {
                        console.log('Message marqué comme lu côté serveur.');
                    })
                    .catch(error => {
                        console.error('Erreur lors du marquage du message comme lu:', error);
                    });
            })
            .listen('MessageRead', (e) => {
                console.log('Message lu événement reçu:', e.messageId, 'par l\'utilisateur', e.readerId);

                if (e.readerId !== currentUserId) {
                    const readReceiptIcon = document.querySelector(`.message-bubble.sent[data-message-id="${e.messageId}"] .fa-check-double`);
                    if (readReceiptIcon) {
                        readReceiptIcon.classList.remove('text-muted');
                        readReceiptIcon.classList.add('text-whatsapp-blue-seen');
                        console.log(`Accusé de lecture mis à jour pour le message ${e.messageId}.`);
                    }
                }
            })
            .error((error) => {
                console.error('Erreur lors de l\'écoute du canal de conversation:', error);
            });
    });

</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/chat.css') }}">
@endpush
