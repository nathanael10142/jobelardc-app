<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Jobela RDC'))</title>

    {{-- Fonts: Nunito est un bon choix pour sa lisibilité, proche de l'esprit WhatsApp --}}
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700,800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5.3.3 CSS (via CDN) - Base de la grille et des composants --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    {{-- Font Awesome 6.5.2 (via CDN) - Indispensable pour les icônes WhatsApp --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Styles WhatsApp intégrés --}}
    <style>
        :root {
            /* Couleurs principales de la palette WhatsApp */
            --whatsapp-green-dark: #008069; /* Le vert en-tête principal */
            --whatsapp-green-light: #128C7E; /* Une nuance plus claire de vert */
            --whatsapp-bg-light: #dadbd3;    /* Le fond gris-vert clair général */
            --whatsapp-text-dark: #333;      /* Couleur de texte générale */
            --whatsapp-text-muted: #666;    /* Texte secondaire, ex: date/heure */
            --whatsapp-border: #e0e0e0;      /* Bordures légères */
            --whatsapp-card-bg: #ffffff;    /* Fond des cartes et éléments blancs */
            --whatsapp-hover-light: #f5f5f5; /* Couleur de survol légère */
            --whatsapp-shadow: rgba(0, 0, 0, 0.12); /* Ombre subtile */
            --whatsapp-chat-bg: url('https://placehold.co/800x600/e9e8de/a8b0bd?text=Fond+chat+Whatsapp'); /* Fond du chat inspiré */
            --whatsapp-bubble-sent: #dcf8c6; /* Couleur de bulle envoyée */
            --whatsapp-bubble-received: #ffffff; /* Couleur de bulle reçue */
            --whatsapp-active-tab-color: #fff; /* Couleur du texte de l'onglet actif */
            --whatsapp-inactive-tab-color: rgba(255, 255, 255, 0.6); /* Couleur du texte de l'onglet inactif */
            --whatsapp-tab-indicator: #fff; /* Couleur de l'indicateur d'onglet actif */
        }

        html, body {
            height: 100%; /* S'assure que HTML et BODY prennent toute la hauteur */
            margin: 0;
            padding: 0;
            overflow: hidden; /* Empêche le défilement général */
        }

        body {
            background-color: var(--whatsapp-bg-light); /* Fond vert très clair */
            font-family: 'Nunito', sans-serif;
            display: flex;
            flex-direction: column; /* Agencement en colonne pour header et main */
            color: var(--whatsapp-text-dark); /* Couleur de texte par défaut */
        }

        #app {
            flex-grow: 1; /* Permet à l'application de prendre l'espace disponible */
            display: flex;
            flex-direction: column; /* Agencement en colonne */
            height: 100%; /* Important pour l'app prenne la hauteur restante */
            overflow: hidden; /* Empêche l'app de déborder */
        }

        /* En-tête de l'application (Barre verte en haut, incluant le titre et les icônes) */
        .whatsapp-header {
            background-color: var(--whatsapp-green-dark); /* Le vert foncé de l'en-tête */
            width: 100%;
            padding: 15px 15px 0px 15px; /* Padding haut, côtés, et pas de padding bas pour laisser la place aux onglets */
            color: var(--whatsapp-card-bg); /* Texte blanc dans l'en-tête */
            box-shadow: 0 2px 5px var(--whatsapp-shadow); /* Ombre douce sous l'en-tête */
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column; /* Pour organiser le titre/icônes et les onglets */
            flex-shrink: 0; /* Empêche le header de rétrécir */
        }
        .whatsapp-header .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px; /* Espacement entre le haut du header et les tabs */
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
            font-size: 1.4rem; /* Taille des icônes de navigation (loupe, 3 points, etc.) */
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

        /* Barre d'onglets (DISCUSSIONS, ACTUALITÉS, APPELS) */
        .whatsapp-tabs {
            display: flex;
            justify-content: space-between; /* Pour espacer les éléments */
            padding-bottom: 5px; /* Espace sous les onglets */
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
        }
        .whatsapp-tabs .tab-item:hover {
            color: var(--whatsapp-active-tab-color);
        }
        .whatsapp-tabs .tab-item.camera-icon {
            flex: 0 0 auto; /* Ne prend pas d'espace flexible */
            width: 40px; /* Taille fixe pour l'icône caméra */
            font-size: 1.4rem;
            color: var(--whatsapp-active-tab-color);
            padding-left: 0;
            padding-right: 0;
        }
        .whatsapp-tabs .tab-item.active {
            color: var(--whatsapp-active-tab-color);
        }
        .whatsapp-tabs .tab-item.active::after {
            content: '';
            position: absolute;
            bottom: -5px; /* Ajuster pour qu'il soit sous le texte */
            left: 50%;
            transform: translateX(-50%);
            width: 70%; /* Largeur de la barre indicatrice */
            height: 4px;
            background-color: var(--whatsapp-tab-indicator);
            border-radius: 2px;
        }
        .whatsapp-tabs .tab-item.unread-badge {
            font-size: 0.7rem;
            background-color: #0d6efd; /* Badge bleu WhatsApp */
            color: white;
            padding: 2px 6px;
            border-radius: 50%;
            position: absolute;
            top: 2px;
            right: 5px;
        }


        /* Wrapper principal pour le contenu des pages */
        .whatsapp-content-wrapper {
            flex-grow: 1; /* Prend l'espace restant */
            display: flex;
            flex-direction: column; /* Agencement en colonne pour son propre contenu */
            padding: 0; /* Pas de padding par défaut ici, les pages gèreront leur padding */
            background-color: var(--whatsapp-bg-light); /* Maintient le fond général */
            overflow-y: auto; /* Permet le défilement si le contenu dépasse */
            position: relative; /* Pour positionner le FAB si utilisé globalement */
        }

        /* Style des cartes de formulaire (Login, Register, etc.) */
        .whatsapp-card {
            background-color: var(--whatsapp-card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 12px var(--whatsapp-shadow);
            border: none;
            width: 100%;
            max-width: 480px; /* Plus adapté aux formulaires sur mobile */
            overflow: hidden;
            margin: 20px auto; /* Centre la carte avec une marge auto */
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

        /* Style des éléments de formulaire */
        .form-control, .form-select, .form-textarea {
            border-radius: 8px; /* Rayons de bordure moins arrondis que WhatsApp pour les inputs */
            border: 1px solid var(--whatsapp-border);
            padding: 10px 15px;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--whatsapp-green-light);
            box-shadow: 0 0 0 0.2rem rgba(18, 140, 126, 0.2); /* Ombre plus subtile */
            outline: none;
        }
        .form-label {
            font-weight: 600;
            color: var(--whatsapp-text-dark);
            margin-bottom: 5px;
            font-size: 0.9rem; /* Labels légèrement plus petits */
        }
        .form-check-input {
            border-radius: 4px;
        }
        .form-check-input:checked {
            background-color: var(--whatsapp-green-dark);
            border-color: var(--whatsapp-green-dark);
        }

        /* Boutons */
        .btn-primary {
            background-color: var(--whatsapp-green-dark);
            border-color: var(--whatsapp-green-dark);
            border-radius: 25px; /* Boutons très arrondis */
            padding: 10px 20px;
            font-weight: 700;
            font-size: 1rem;
            transition: background-color 0.2s, border-color 0.2s, transform 0.1s; /* Ajout de transform */
        }
        .btn-primary:hover {
            background-color: var(--whatsapp-green-light);
            border-color: var(--whatsapp-green-light);
            transform: translateY(-1px); /* Léger effet de soulèvement */
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
        /* Style pour les boutons sociaux comme Google */
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

        /* Messages d'erreur de validation */
        .invalid-feedback {
            font-size: 0.8rem;
            margin-top: 5px;
            color: #dc3545;
        }

        /* Bouton flottant d'action (FAB) */
        .fab-button {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: var(--whatsapp-green-light); /* Vert clair de WhatsApp */
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

        /* Responsive adjustments */
        @media (max-width: 767px) {
            .whatsapp-header {
                height: auto; /* Laisser la hauteur s'adapter au contenu */
                padding-bottom: 0; /* Les tabs gèrent leur propre padding */
            }
            .whatsapp-header .header-top {
                margin-bottom: 5px; /* Moins d'espace sous le titre/icônes sur mobile */
            }
            .whatsapp-header .app-title {
                font-size: 1.4rem; /* Plus petit sur mobile */
            }
            .whatsapp-header .app-title i {
                font-size: 1.1rem;
            }
            .whatsapp-header .nav-icons .nav-link {
                font-size: 1.2rem; /* Icônes plus petites sur mobile */
            }
            .whatsapp-tabs .tab-item {
                font-size: 0.85rem; /* Texte des onglets plus petit */
                padding: 12px 0; /* Plus de padding pour la zone de clic */
            }
            .whatsapp-tabs .tab-item.camera-icon {
                width: 50px; /* Taille pour le bouton caméra */
                font-size: 1.6rem;
                border-bottom: 3px solid transparent; /* Ligne indicatrice sous l'icône */
            }
            .whatsapp-tabs .tab-item.camera-icon.active {
                border-bottom-color: var(--whatsapp-tab-indicator);
            }
            .whatsapp-tabs .tab-item.active::after {
                bottom: 0; /* Remonter l'indicateur sous le texte/icône */
                height: 3px; /* Barre indicatrice plus fine */
            }

            /* Cacher le nom de l'utilisateur dans la navbar dropdown sur mobile si désiré */
            .whatsapp-header .dropdown-toggle .d-md-none {
                display: inline !important;
            }
            .whatsapp-header .dropdown-toggle .d-none.d-md-inline {
                display: none !important;
            }
        }

        @media (min-width: 768px) {
            .whatsapp-header {
                height: 100px; /* Plus haut sur desktop */
                padding-bottom: 15px; /* Ajuste le padding bottom pour desktop */
                padding-left: 10%; /* Marge plus grande sur desktop */
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
                justify-content: flex-start; /* Alignement à gauche des onglets sur desktop */
                gap: 30px; /* Espace entre les onglets */
            }
            .whatsapp-tabs .tab-item {
                flex: none; /* Ne pas prendre d'espace flexible sur desktop */
                padding: 8px 15px; /* Plus de padding pour les onglets */
                font-size: 1rem;
            }
            .whatsapp-tabs .tab-item.camera-icon {
                display: none; /* Cache l'icône caméra sur desktop ou la déplace */
            }
            .whatsapp-tabs .tab-item.active::after {
                bottom: -5px; /* Replacer l'indicateur sous le texte */
                height: 4px;
            }
            .whatsapp-card {
                max-width: 520px; /* Plus large sur desktop */
                margin-top: 40px; /* Plus d'espace au-dessus */
            }
        }
    </style>
    {{-- Section pour les styles spécifiques à la page --}}
    @stack('styles')
</head>
<body>
    <div id="app">
        {{-- En-tête principal de l'application - Inspiré par la barre supérieure de WhatsApp --}}
        <header class="whatsapp-header">
            <div class="header-top">
                {{-- L'URL '/' pointe maintenant vers les annonces du marché via la route 'home' --}}
                <a class="app-title" href="{{ route('home') }}">
                    <i class="fab fa-whatsapp"></i> Jobela RDC
                </a>

                <ul class="navbar-nav nav-icons">
                    {{-- Liens/icônes de navigation (search, menu) --}}
                    <li class="nav-item">
                        <a class="nav-link" href="#" title="Rechercher">
                            <i class="fas fa-search"></i>
                        </a>
                    </li>
                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item d-md-none"> {{-- Visible seulement sur mobile --}}
                                <a class="nav-link" href="{{ route('login') }}" title="{{ __('Se connecter') }}">
                                    <i class="fas fa-sign-in-alt"></i>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('register'))
                            <li class="nav-item d-md-none"> {{-- Visible seulement sur mobile --}}
                                <a class="nav-link" href="{{ route('register') }}" title="{{ __('S\'inscrire') }}">
                                    <i class="fas fa-user-plus"></i>
                                </a>
                            </li>
                        @endif
                    @else
                        {{-- Menu déroulant pour l'utilisateur connecté --}}
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{-- Afficher l'avatar et le nom/icône --}}
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
                                            // Fallback to initials avatar if no profile picture
                                            $initials = '';
                                            if ($user->name) {
                                                $words = explode(' ', $user->name);
                                                foreach ($words as $word) {
                                                    $initials .= strtoupper(substr($word, 0, 1));
                                                }
                                                // Ensure initials are max 2 characters
                                                if (strlen($initials) > 2) {
                                                    $initials = substr($initials, 0, 2);
                                                }
                                            } else {
                                                $initials = '??';
                                            }
                                            // Generate consistent color based on user ID or email
                                            $bgColor = '#' . substr(md5($user->email ?? $user->id ?? uniqid()), 0, 6);
                                            $avatarHtml = '<div class="navbar-avatar-text-placeholder" style="background-color: ' . $bgColor . ';">' . $initials . '</div>';
                                        }
                                    } else {
                                        // Fallback for anonymous or deleted user
                                        $avatarHtml = '<div class="navbar-avatar-text-placeholder" style="background-color: #999;"><i class="fas fa-user-circle"></i></div>';
                                    }
                                @endphp
                                {!! $avatarHtml !!}
                                <span class="d-none d-md-inline">{{ Auth::user()->name ?? 'Utilisateur' }}</span>
                                <i class="fas fa-ellipsis-v d-md-none"></i> {{-- Icône menu 3 points sur mobile --}}
                            </a>

                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                {{-- Liens spécifiques utilisateur --}}
                                <a class="dropdown-item" href="{{ route('profile.index') }}">
                                    <i class="fas fa-user me-2"></i> {{ __('Mon Profil') }}
                                </a>
                                {{-- Nouvelle entrée pour les Annonces du Marché --}}
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
                                {{-- Nouveaux liens : Paramètres et Paiement --}}
                                <a class="dropdown-item" href="{{ route('settings.index') }}">
                                    <i class="fas fa-cog me-2"></i> {{ __('Paramètres') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('payment.index') }}">
                                    <i class="fas fa-gem me-2"></i> {{ __('Premium & Paiements') }}
                                </a>
                                <hr class="dropdown-divider">
                                {{-- Lien de déconnexion --}}
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

            {{-- Barre d'onglets (DISCUSSIONS, ACTUALITÉS, APPELS) --}}
            <nav class="whatsapp-tabs">
                <a href="{{ route('camera.index') }}" class="tab-item camera-icon" title="Ouvrir la caméra"><i class="fas fa-camera"></i></a>
                <a href="{{ route('chats.index') }}" class="tab-item" id="tab-chats">DISCUSSIONS</a>
                <a href="{{ route('status.index') }}" class="tab-item" id="tab-status">ACTUALITÉS</a>
                <a href="{{ route('calls.index') }}" class="tab-item" id="tab-calls">APPELS</a>
            </nav>
        </header>

        {{-- Wrapper principal pour le contenu des pages (le contenu réel des onglets) --}}
        <main class="whatsapp-content-wrapper">
            @yield('content')
        </main>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    {{-- Script pour passer l'ID de l'utilisateur au JavaScript global --}}
    @auth
    <script>
        // Rendre les données utilisateur disponibles globalement pour JavaScript
        window.Laravel = {
            user: {
                id: {{ Auth::user()->id }},
                name: "{{ Auth::user()->name }}",
                // Ajoutez d'autres données utilisateur nécessaires ici si vous en avez besoin dans le frontend
            }
        };
        console.log('window.Laravel.user initialized:', window.Laravel.user); // AJOUTÉ POUR DEBUG
    </script>
    @endauth

    {{-- Votre fichier app.js compilé (qui importe calls.js et initialise Echo) --}}
    {{-- DOIT ÊTRE CHARGÉ APRÈS window.Laravel et AVANT les scripts spécifiques à la page --}}
    @vite('resources/js/app.js') {{-- C'est la ligne clé pour Vite --}}
    {{-- OU si vous utilisez Laravel Mix : <script src="{{ asset('js/app.js') }}"></script> --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;

            // Function to set active class based on URL
            function setActiveLink() {
                // Remove active from all tabs and dropdown items first
                document.querySelectorAll('.whatsapp-tabs .tab-item').forEach(item => item.classList.remove('active'));
                document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => item.classList.remove('active'));

                // Set active for tab items
                if (currentPath.startsWith('{{ route('chats.index', [], false) }}')) { // Use startsWith for chat conversations
                    document.getElementById('tab-chats').classList.add('active');
                } else if (currentPath.startsWith('{{ route('status.index', [], false) }}')) {
                    document.getElementById('tab-status').classList.add('active');
                } else if (currentPath.startsWith('{{ route('calls.index', [], false) }}')) {
                    document.getElementById('tab-calls').classList.add('active');
                } else if (currentPath.startsWith('{{ route('camera.index', [], false) }}')) {
                     document.querySelector('.camera-icon').classList.add('active');
                } else if (currentPath === '{{ route('home', [], false) }}' || currentPath.startsWith('{{ route('listings.index', [], false) }}')) {
                    // For the home/listings page, no tab might be active or you could activate 'Chats' if it's the default.
                    // For now, no specific tab is highlighted.
                }

                // Set active for dropdown items based on their href
                document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
                    const itemPath = new URL(item.href).pathname;
                    if (itemPath === currentPath) {
                        item.classList.add('active');
                    }
                });
            }

            // Call it on page load
            setActiveLink();
        });
    </script>

    {{-- Section pour les scripts spécifiques à la page --}}
    @stack('scripts')
</body>
</html>
