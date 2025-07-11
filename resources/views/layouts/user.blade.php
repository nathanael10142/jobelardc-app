<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Jobela RDC'))</title>

    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700,800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        :root {
            --whatsapp-green-dark: #008069;
            --whatsapp-green-light: #128C7E;
            --whatsapp-bg-light: #dadbd3;
            --whatsapp-text-dark: #333;
            --whatsapp-text-muted: #666;
            --whatsapp-border: #e0e0e0;
            --whatsapp-card-bg: #ffffff;
            --whatsapp-hover-light: #f5f5f5;
            --whatsapp-shadow: rgba(0, 0, 0, 0.12);
            --whatsapp-chat-bg: url('https://placehold.co/800x600/e9e8de/a8b0bd?text=Fond+chat+Whatsapp');
            --whatsapp-bubble-sent: #dcf8c6;
            --whatsapp-bubble-received: #ffffff;
            --whatsapp-active-tab-color: #fff;
            --whatsapp-inactive-tab-color: rgba(255, 255, 255, 0.6);
            --whatsapp-tab-indicator: #fff;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            background-color: var(--whatsapp-bg-light);
            font-family: 'Nunito', sans-serif;
            display: flex;
            flex-direction: column;
            color: var(--whatsapp-text-dark);
        }

        #app {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .whatsapp-header {
            background-color: var(--whatsapp-green-dark);
            width: 100%;
            padding: 15px 15px 0px 15px;
            color: var(--whatsapp-card-bg);
            box-shadow: 0 2px 5px var(--whatsapp-shadow);
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        .whatsapp-header .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .whatsapp-header .app-title {
            color: var(--whatsapp-card-bg);
            font-weight: 700;
            font-size: 1.6rem;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .whatsapp-header .app-title:hover {
            color: var(--whatsapp-card-bg);
        }
        .whatsapp-header .app-title i {
            margin-right: 8px;
            font-size: 1.3rem;
        }

        .whatsapp-header .nav-icons {
            display: flex;
            align-items: center;
        }
        .whatsapp-header .nav-icons .nav-item {
            margin-left: 15px;
        }
        .whatsapp-header .nav-icons .nav-link {
            color: var(--whatsapp-card-bg) !important;
            font-size: 1.4rem;
            opacity: 0.9;
            transition: opacity 0.2s;
        }
        .whatsapp-header .nav-icons .nav-link:hover {
            opacity: 1;
        }
        .whatsapp-header .dropdown-menu {
            background-color: var(--whatsapp-card-bg);
            border: 1px solid var(--whatsapp-border);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--whatsapp-shadow);
            padding: 5px 0;
            min-width: 160px;
        }
        .whatsapp-header .dropdown-item {
            color: var(--whatsapp-text-dark);
            padding: 10px 20px;
            font-size: 0.95rem;
            transition: background-color 0.2s;
        }
        .whatsapp-header .dropdown-item:hover {
            background-color: var(--whatsapp-hover-light);
        }
        .navbar-avatar-thumbnail {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .navbar-avatar-text-placeholder {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: white;
            background-color: #555;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .navbar-avatar-text-placeholder i {
            font-size: 1.2rem;
        }


        .whatsapp-tabs {
            display: flex;
            justify-content: space-between;
            padding-bottom: 5px;
            position: relative;
            z-index: 5;
        }
        .whatsapp-tabs .tab-item {
            flex: 1;
            text-align: center;
            padding: 8px 0;
            color: var(--whatsapp-inactive-tab-color);
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            text-decoration: none;
            position: relative;
            transition: color 0.2s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .whatsapp-tabs .tab-item:hover {
            color: var(--whatsapp-active-tab-color);
        }
        .whatsapp-tabs .tab-item.camera-icon {
            flex: 0 0 auto;
            width: 40px;
            font-size: 1.4rem;
            color: var(--whatsapp-active-tab-color);
            padding-left: 0;
            padding-right: 0;
        }
        .whatsapp-tabs .tab-item.camera-icon.active {
            border-bottom: 3px solid transparent;
        }
        .whatsapp-tabs .tab-item.camera-icon.active {
            border-bottom-color: var(--whatsapp-tab-indicator);
        }
        .whatsapp-tabs .tab-item.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 70%;
            height: 4px;
            background-color: var(--whatsapp-tab-indicator);
            border-radius: 2px;
        }
        .whatsapp-tabs .unread-badge {
            font-size: 0.7rem;
            background-color: #0d6efd;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: static;
            transform: none;
            top: auto;
            right: auto;
            margin-left: 5px;
        }


        .whatsapp-content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            padding: 0;
            background-color: var(--whatsapp-bg-light);
            overflow-y: auto;
            position: relative;
        }

        .whatsapp-card {
            background-color: var(--whatsapp-card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 12px var(--whatsapp-shadow);
            border: none;
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            margin: 20px auto;
        }
        .whatsapp-card .card-header {
            background-color: var(--whatsapp-hover-light);
            color: var(--whatsapp-text-dark);
            font-weight: 700;
            border-bottom: 1px solid var(--whatsapp-border);
            padding: 18px 20px;
            font-size: 1.1rem;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .whatsapp-card .card-body {
            padding: 25px 20px;
        }

        .form-control, .form-select, .form-textarea {
            border-radius: 8px;
            border: 1px solid var(--whatsapp-border);
            padding: 10px 15px;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--whatsapp-green-light);
            box-shadow: 0 0 0 0.2rem rgba(18, 140, 126, 0.2);
            outline: none;
        }
        .form-label {
            font-weight: 600;
            color: var(--whatsapp-text-dark);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .form-check-input {
            border-radius: 4px;
        }
        .form-check-input:checked {
            background-color: var(--whatsapp-green-dark);
            border-color: var(--whatsapp-green-dark);
        }

        .btn-primary {
            background-color: var(--whatsapp-green-dark);
            border-color: var(--whatsapp-green-dark);
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 1rem;
            transition: background-color 0.2s, border-color 0.2s, transform 0.1s;
        }
        .btn-primary:hover {
            background-color: var(--whatsapp-green-light);
            border-color: var(--whatsapp-green-light);
            transform: translateY(-1px);
        }
        .btn-link {
            color: var(--whatsapp-green-light);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .btn-link:hover {
            color: var(--whatsapp-green-dark);
            text-decoration: underline;
        }
        .btn-danger {
            background-color: #DB4437 !important;
            border-color: #DB4437 !important;
            border-radius: 25px;
            padding: 10px 20px;
            color: white;
            font-weight: 700;
            transition: background-color 0.2s ease-in-out;
        }
        .btn-danger:hover {
            background-color: #C1352A !important;
            border-color: #C1352A !important;
        }
        .btn-danger .fab {
            margin-right: 8px;
        }

        .invalid-feedback {
            font-size: 0.8rem;
            margin-top: 5px;
            color: #dc3545;
        }

        .fab-button {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: var(--whatsapp-green-light);
            color: white;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.2s, transform 0.1s;
            z-index: 100;
        }
        .fab-button:hover {
            background-color: var(--whatsapp-green-dark);
            transform: scale(1.05);
        }

        @media (max-width: 767px) {
            .whatsapp-header {
                height: auto;
                padding-bottom: 0;
            }
            .whatsapp-header .header-top {
                margin-bottom: 5px;
            }
            .whatsapp-header .app-title {
                font-size: 1.4rem;
            }
            .whatsapp-header .app-title i {
                font-size: 1.1rem;
            }
            .whatsapp-header .nav-icons .nav-link {
                font-size: 1.2rem;
            }
            .whatsapp-tabs .tab-item {
                font-size: 0.85rem;
                padding: 12px 0;
            }
            .whatsapp-tabs .tab-item.camera-icon {
                width: 50px;
                font-size: 1.6rem;
                border-bottom: 3px solid transparent;
            }
            .whatsapp-tabs .tab-item.camera-icon.active {
                border-bottom-color: var(--whatsapp-tab-indicator);
            }
            .whatsapp-tabs .tab-item.active::after {
                bottom: 0;
                height: 3px;
            }

            .whatsapp-header .dropdown-toggle .d-md-none {
                display: inline !important;
            }
            .whatsapp-header .dropdown-toggle .d-none.d-md-inline {
                display: none !important;
            }
            .whatsapp-tabs .unread-badge {
                position: static;
                margin-left: 5px;
                transform: none;
            }
        }

        @media (min-width: 768px) {
            .whatsapp-header {
                height: 100px;
                padding-bottom: 15px;
                padding-left: 10%;
                padding-right: 10%;
            }
            .whatsapp-header .header-top {
                margin-bottom: 15px;
            }
            .whatsapp-header .app-title {
                font-size: 1.8rem;
            }
            .whatsapp-header .app-title i {
                font-size: 1.5rem;
            }
            .whatsapp-header .nav-icons .nav-link {
                font-size: 1.5rem;
            }
            .whatsapp-tabs {
                justify-content: flex-start;
                gap: 30px;
            }
            .whatsapp-tabs .tab-item {
                flex: none;
                padding: 8px 15px;
                font-size: 1rem;
            }
            .whatsapp-tabs .tab-item.camera-icon {
                display: none;
            }
            .whatsapp-tabs .tab-item.active::after {
                bottom: -5px;
                height: 4px;
            }
            .whatsapp-card {
                max-width: 520px;
                margin-top: 40px;
            }
        }
    </style>
    @stack('styles')

    @routes

</head>
<body @auth data-user-id="{{ Auth::id() }}" @endauth>
    <div id="app">
        <header class="whatsapp-header">
            <div class="header-top">
                <a class="app-title" href="{{ route('home') }}">
                    <i class="fab fa-whatsapp"></i> Jobela RDC
                </a>

                <ul class="navbar-nav nav-icons">
                    <li class="nav-item">
                        <a class="nav-link" href="#" title="Rechercher">
                            <i class="fas fa-search"></i>
                        </a>
                    </li>
                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item d-md-none">
                                <a class="nav-link" href="{{ route('login') }}" title="{{ __('Se connecter') }}">
                                    <i class="fas fa-sign-in-alt"></i>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('register'))
                            <li class="nav-item d-md-none">
                                <a class="nav-link" href="{{ route('register') }}" title="{{ __('S\'inscrire') }}">
                                    <i class="fas fa-user-plus"></i>
                                </a>
                            </li>
                        @endif
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                @php
                                    $user = Auth::user();
                                    $avatarHtml = '';

                                    if ($user) {
                                        $avatarPath = $user->profile_picture ?? null;
                                        $isExternal = $avatarPath && Str::startsWith($avatarPath, ['http://', 'https://']);

                                        if ($avatarPath) {
                                            $avatarSrc = $isExternal ? $avatarPath : asset('storage/' . $avatarPath);
                                            $avatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil" class="navbar-avatar-thumbnail">';
                                        } else {
                                            $initials = '';
                                            if ($user->name) {
                                                $words = explode(' ', $user->name);
                                                foreach ($words as $word) {
                                                    $initials .= strtoupper(substr($word, 0, 1));
                                                }
                                                if (strlen($initials) > 2) {
                                                    $initials = substr($initials, 0, 2);
                                                }
                                            } else {
                                                $initials = '??';
                                            }
                                            $bgColor = '#' . substr(md5($user->email ?? $user->id ?? uniqid()), 0, 6);
                                            $avatarHtml = '<div class="navbar-avatar-text-placeholder" style="background-color: ' . $bgColor . ';">' . $initials . '</div>';
                                        }
                                    } else {
                                        $avatarHtml = '<div class="navbar-avatar-text-placeholder" style="background-color: #999;"><i class="fas fa-user-circle"></i></div>';
                                    }
                                @endphp
                                {!! $avatarHtml !!}
                                <span class="d-none d-md-inline">{{ Auth::user()->name ?? 'Utilisateur' }}</span>
                                <i class="fas fa-ellipsis-v d-md-none"></i>
                            </a>

                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('profile.index') }}">
                                    <i class="fas fa-user me-2"></i> {{ __('Mon Profil') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('listings.index') }}">
                                    <i class="fas fa-store me-2"></i> {{ __('Annonces du Marché') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('applications.index') }}">
                                    <i class="fas fa-file-alt me-2"></i> {{ __('Mes Demandes/Offres') }}
                                </a>

                                <a class="dropdown-item" href="{{ route('groups.index') }}">
                                    <i class="fas fa-users me-2"></i> {{ __('Groupes de discussion') }}
                                </a>
                                <hr class="dropdown-divider">
                                <a class="dropdown-item" href="{{ route('settings.index') }}">
                                    <i class="fas fa-cog me-2"></i> {{ __('Paramètres') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('payment.index') }}">
                                    <i class="fas fa-gem me-2"></i> {{ __('Premium & Paiements') }}
                                </a>
                                <hr class="dropdown-divider">
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                 document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt me-2"></i> {{ __('Déconnexion') }}
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @endguest
                </ul>
            </div>

            <nav class="whatsapp-tabs">
                <a href="{{ route('camera.index') }}" class="tab-item camera-icon" title="Ouvrir la caméra"><i class="fas fa-camera"></i></a>
                <a href="{{ route('chats.index') }}" class="tab-item" id="tab-chats">
                    DISCUSSIONS
                    <span class="unread-badge d-none" id="chats-badge">0</span>
                </a>
                <a href="{{ route('status.index') }}" class="tab-item" id="tab-status">
                    ACTUALITÉS
                    <span class="unread-badge d-none" id="status-badge">0</span>
                </a>
                <a href="{{ route('calls.index') }}" class="tab-item" id="tab-calls">
                    APPELS
                    <span class="unread-badge d-none" id="calls-badge">0</span>
                </a>
            </nav>
        </header>

        <main class="whatsapp-content-wrapper">
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        window.Laravel = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            user: @json(Auth::user()),
        };
        console.log('window.Laravel initialized:', window.Laravel);
    </script>

    @vite('resources/js/app.js')

    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;

            function setActiveLink() {
                document.querySelectorAll('.whatsapp-tabs .tab-item').forEach(item => item.classList.remove('active'));
                document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => item.classList.remove('active'));

                if (currentPath.startsWith(route('chats.index', [], false))) {
                    document.getElementById('tab-chats')?.classList.add('active');
                } else if (currentPath.startsWith(route('status.index', [], false))) {
                    document.getElementById('tab-status')?.classList.add('active');
                } else if (currentPath.startsWith(route('calls.index', [], false))) {
                    document.getElementById('tab-calls')?.classList.add('active');
                } else if (currentPath.startsWith(route('camera.index', [], false))) {
                    document.querySelector('.camera-icon')?.classList.add('active');
                } else if (currentPath === route('home', [], false) || currentPath.startsWith(route('listings.index', [], false))) {
                    
                }

                document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
                    const itemPath = new URL(item.href).pathname;
                    if (itemPath === route('profile.index', [], false) && currentPath === itemPath) {
                        item.classList.add('active');
                    } else if (itemPath === route('listings.index', [], false) && currentPath === itemPath) {
                        item.classList.add('active');
                    } else if (itemPath === route('applications.index', [], false) && currentPath === itemPath) {
                        item.classList.add('active');
                    } else if (itemPath === route('groups.index', [], false) && currentPath === itemPath) {
                        item.classList.add('active');
                    } else if (itemPath === route('settings.index', [], false) && currentPath === itemPath) {
                        item.classList.add('active');
                    } else if (itemPath === route('payment.index', [], false) && currentPath === itemPath) {
                        item.classList.add('active');
                    }
                });
            }
            setActiveLink();

            async function fetchUnreadCounts() {
                if (!window.Laravel || !window.Laravel.user) {
                    console.warn("Utilisateur non connecté ou window.Laravel non initialisé. Impossible de récupérer les comptes non lus.");
                    return;
                }

                try {
                    const unreadChatsResponse = await fetch(route('api.unread.chats'));
                    const unreadChatsData = await unreadChatsResponse.json();
                    const chatsBadge = document.getElementById('chats-badge');
                    if (chatsBadge) {
                        if (unreadChatsData.count > 0) {
                            chatsBadge.textContent = unreadChatsData.count;
                            chatsBadge.classList.remove('d-none');
                        } else {
                            chatsBadge.classList.add('d-none');
                        }
                    }

                    const unreadStatusResponse = await fetch(route('api.unread.status'));
                    const unreadStatusData = await unreadStatusResponse.json();
                    const statusBadge = document.getElementById('status-badge');
                    if (statusBadge) {
                        if (unreadStatusData.count > 0) {
                            statusBadge.textContent = unreadStatusData.count;
                            statusBadge.classList.remove('d-none');
                        } else {
                            statusBadge.classList.add('d-none');
                        }
                    }

                    const unreadCallsResponse = await fetch(route('api.unread.calls'));
                    const unreadCallsData = await unreadCallsResponse.json();
                    const callsBadge = document.getElementById('calls-badge');
                    if (callsBadge) {
                        if (unreadCallsData.count > 0) {
                            callsBadge.textContent = unreadCallsData.count;
                            callsBadge.classList.remove('d-none');
                        } else {
                            callsBadge.classList.add('d-none');
                        }
                    }

                } catch (error) {
                    console.error("Erreur lors de la récupération des comptes non lus:", error);
                }
            }

            fetchUnreadCounts();
            setInterval(fetchUnreadCounts, 30000);
        });
    </script>
</body>
</html>
