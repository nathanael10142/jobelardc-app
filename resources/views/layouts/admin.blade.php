<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Jobela RDC') }} - Admin</title> {{-- Ajout de " - Admin" au titre pour identifier la section --}}

    {{-- Fonts: Nunito est un bon choix pour sa lisibilité, proche de l'esprit WhatsApp --}}
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700,800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5.3.3 CSS (via CDN) - Base de la grille et des composants --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    {{-- Font Awesome 6.5.2 (via CDN) - Indispensable pour les icônes WhatsApp --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Styles WhatsApp MÉTICULEUX --}}
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
        }

        body {
            background-color: var(--whatsapp-bg-light); /* Fond vert très clair */
            font-family: 'Nunito', sans-serif;
            min-height: 100vh; /* S'assure que le corps prend toute la hauteur */
            display: flex;
            flex-direction: column;
            color: var(--whatsapp-text-dark); /* Couleur de texte par défaut */
        }
        #app {
            flex-grow: 1; /* Permet à l'application de prendre l'espace disponible */
            display: flex;
            flex-direction: column;
        }

        /* En-tête de l'application (Barre verte en haut) */
        .whatsapp-header {
            background-color: var(--whatsapp-green-dark); /* Le vert foncé de l'en-tête */
            height: 100px; /* Hauteur définie */
            width: 100%;
            display: flex;
            align-items: flex-end; /* Alignements des éléments en bas de la barre */
            padding-bottom: 18px; /* Espacement interne pour le contenu */
            padding-left: 10%; /* Marge sur les côtés, comme l'appli desktop */
            padding-right: 10%;
            color: var(--whatsapp-card-bg); /* Texte blanc dans l'en-tête */
            box-shadow: 0 2px 5px var(--whatsapp-shadow); /* Ombre douce sous l'en-tête */
            position: relative;
            z-index: 10;
        }
        .whatsapp-header .navbar-brand {
            color: var(--whatsapp-card-bg);
            font-weight: 700; /* Plus de gras pour le titre */
            font-size: 1.8rem; /* Taille de police légèrement plus grande */
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .whatsapp-header .navbar-brand:hover {
            color: var(--whatsapp-card-bg); /* Pas de changement de couleur au survol pour le titre */
        }
        .whatsapp-header .navbar-brand i { /* Icône optionnelle à côté du titre */
            margin-right: 8px;
            font-size: 1.5rem;
        }

        .whatsapp-header .navbar-nav {
            margin-left: auto; /* Pousse les liens d'auth à droite */
        }
        .whatsapp-header .nav-item {
            /* Espacement entre les éléments de la nav - réduit pour les icônes */
            margin-left: 15px; 
        }
        .whatsapp-header .nav-link {
            color: var(--whatsapp-card-bg) !important;
            font-size: 1.05rem; /* Taille de police légèrement ajustée */
            font-weight: 600; /* Texte plus audacieux */
            transition: color 0.2s ease-in-out;
            opacity: 0.9; /* Légèrement moins opaque pour un look plus subtil */
            display: flex; /* Permet d'aligner l'icône et le texte (si tu les remets) */
            align-items: center;
        }
        /* Style spécifique pour les icônes de navigation */
        .whatsapp-header .nav-link i {
            font-size: 1.5rem; /* Taille de l'icône */
            line-height: 1; /* Assure que l'icône est bien centrée */
        }

        .whatsapp-header .nav-link:hover {
            color: #dcf8c6 !important; /* Vert clair de survol, très proche de WhatsApp */
            opacity: 1;
        }
        .whatsapp-header .dropdown-menu {
            background-color: var(--whatsapp-card-bg); /* Fond blanc pour le menu déroulant */
            border: 1px solid var(--whatsapp-border); /* Bordure légère */
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--whatsapp-shadow);
            padding: 5px 0;
            min-width: 160px; /* Largeur minimale du dropdown */
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


        /* Wrapper pour le contenu, sans centrage forcé pour une layout plus flexible (idéal pour admin) */
        .whatsapp-content-wrapper {
            flex-grow: 1;
            /* Suppression des styles de centrage qui sont bons pour les formulaires mais pas pour un layout étendu */
            /* justify-content: center; */
            /* align-items: center; */
            padding: 40px 20px; /* Plus de padding autour du contenu */
            background-color: var(--whatsapp-bg-light); /* Maintient le fond général */
        }

        /* Style des cartes de formulaire (Login, Register) - Applicables partout */
        .whatsapp-card {
            background-color: var(--whatsapp-card-bg); /* Fond blanc pur */
            border-radius: 10px; /* Rayons de bordure légèrement plus prononcés */
            box-shadow: 0 4px 12px var(--whatsapp-shadow); /* Ombre plus distincte */
            border: none;
            width: 100%;
            /* Suppression de la largeur maximale fixe pour permettre une meilleure occupation de l'espace sur PC */
            /* max-width: 520px; */ 
            overflow: hidden; /* Assure que le border-radius s'applique bien au contenu */
        }
        .whatsapp-card .card-header {
            background-color: var(--whatsapp-hover-light); /* Gris clair subtil pour l'en-tête de carte */
            color: var(--whatsapp-text-dark);
            font-weight: 700; /* Gras pour le titre de carte */
            border-bottom: 1px solid var(--whatsapp-border); /* Ligne de séparation fine */
            padding: 20px 25px; /* Espacement ajusté */
            font-size: 1.2rem;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .whatsapp-card .card-body {
            padding: 30px 25px; /* Espacement intérieur du corps de carte */
        }

        /* Style des éléments de formulaire */
        .form-control, .form-select, .form-textarea { /* Ajout de form-select et form-textarea */
            border-radius: 25px; /* Rayons de bordure arrondis comme dans WhatsApp */
            border: 1px solid var(--whatsapp-border);
            padding: 12px 18px; /* Plus de padding pour une meilleure ergonomie */
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus, .form-textarea:focus { /* Ajout de form-select et form-textarea */
            border-color: var(--whatsapp-green-light); /* Vert WhatsApp au focus */
            box-shadow: 0 0 0 0.25rem rgba(18, 140, 126, 0.25); /* Ombre verte subtile au focus */
            outline: none; /* Supprime l'outline par défaut du navigateur */
        }
        .form-label {
            font-weight: 600; /* Labels plus audacieux */
            color: var(--whatsapp-text-dark);
            margin-bottom: 8px; /* Espace sous le label */
        }
        .form-check-input {
            border-radius: 4px; /* Un peu moins arrondi pour les checkboxes */
        }
        .form-check-input:checked {
            background-color: var(--whatsapp-green-dark);
            border-color: var(--whatsapp-green-dark);
        }

        /* Boutons */
        .btn-primary {
            background-color: var(--whatsapp-green-dark); /* Vert foncé WhatsApp pour les boutons d'action */
            border-color: var(--whatsapp-green-dark);
            border-radius: 25px; /* Boutons très arrondis */
            padding: 12px 25px;
            font-weight: 700; /* Texte des boutons en gras */
            font-size: 1.05rem;
            transition: background-color 0.2s, border-color 0.2s;
        }
        .btn-primary:hover {
            background-color: var(--whatsapp-green-light); /* Nuance de vert légèrement différente au survol */
            border-color: var(--whatsapp-green-light);
        }
        .btn-link {
            color: var(--whatsapp-green-light); /* Liens dans le corps du formulaire en vert */
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .btn-link:hover {
            color: var(--whatsapp-green-dark);
            text-decoration: underline;
        }
        /* Style pour les boutons sociaux comme Google */
        .btn-danger { /* Utilisez cette classe pour votre bouton Google */
            background-color: #DB4437 !important; /* Rouge Google */
            border-color: #DB4437 !important;
            border-radius: 25px;
            padding: 10px 20px;
            color: white;
            font-weight: 700;
            transition: background-color 0.2s ease-in-out;
        }
        .btn-danger:hover {
            background-color: #C1352A !important; /* Rouge plus foncé au survol */
            border-color: #C1352A !important;
        }
        .btn-danger .fab {
            margin-right: 8px; /* Espacement pour l'icône Google */
        }


        /* Messages d'erreur de validation */
        .invalid-feedback {
            font-size: 0.82rem;
            margin-top: 5px; /* Espacement entre l'input et l'erreur */
            color: #dc3545; /* Rouge standard pour les erreurs */
        }

        /* Ajustements mineurs pour la disposition des lignes de formulaire */
        .row.mb-3 {
            margin-bottom: 1.5rem !important; /* Espacement plus cohérent entre les lignes de formulaire */
        }
        .row.mb-0 {
            margin-bottom: 0 !important;
        }

        /* Classes pour les erreurs de validation client-side (compatibilité avec votre JS) */
        .border-red-500 {
            border-color: #dc3545 !important;
        }

        /*
           Ajustements spécifiques pour les écrans plus petits (Maintien de la responsivité de base)
           La version 'Admin' reste principalement orientée PC, mais assure une lisibilité mobile.
        */
        @media (max-width: 767px) {
            .whatsapp-header {
                padding-left: 5%;
                padding-right: 5%;
                height: 80px; /* Un peu moins haut sur mobile */
            }
            .whatsapp-header .navbar-brand {
                font-size: 1.5rem;
            }
            .whatsapp-header .navbar-brand i {
                font-size: 1.2rem;
            }
            .whatsapp-header .nav-item {
                margin-left: 10px;
            }
            .whatsapp-header .nav-link {
                font-size: 0.9rem;
            }
            .whatsapp-header .nav-link i {
                font-size: 1.2rem;
            }
            .whatsapp-card {
                margin-top: 20px; /* Plus d'espace au-dessus de la carte sur mobile */
            }
            .whatsapp-card .card-header {
                font-size: 1rem;
                padding: 15px 20px;
            }
            .whatsapp-card .card-body {
                padding: 20px;
            }
            .form-control, .form-select, .form-textarea, .btn-primary, .btn-danger {
                font-size: 0.9rem;
                padding: 10px 15px;
            }
            .form-label {
                text-align: left !important; /* Force l'alignement à gauche sur mobile */
                width: 100%;
            }
            .col-md-4, .col-md-6, .col-md-8, .col-md-offset-4, .offset-md-2, .offset-md-4 {
                /* Ces classes Bootstrap gèrent déjà le 100% width sur mobile par défaut, donc pas besoin de les forcer */
                /* width: 100%; */
                margin-left: 0 !important;
                padding-left: 0;
                padding-right: 0;
            }
            .row.mb-3 {
                flex-direction: column; /* Force les éléments à s'empiler sur mobile */
                align-items: flex-start;
            }
        }
    </style>

    {{-- Section pour les styles spécifiques à la page --}}
    @yield('styles')
</head>
<body>
    <div id="app">
        {{-- En-tête principal de l'application - Inspiré par la barre supérieure de WhatsApp --}}
        <header class="whatsapp-header">
            <div class="container d-flex align-items-end justify-content-between h-100">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="fab fa-whatsapp"></i> Jobela RDC Admin {{-- Icône WhatsApp pour le branding, ajouté "Admin" --}}
                </a>

                <ul class="navbar-nav d-flex flex-row">
                    {{-- Liens d'authentification sous forme d'icônes --}}
                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}" title="{{ __('Se connecter') }}">
                                    <i class="fas fa-sign-in-alt"></i> {{-- Icône de connexion --}}
                                </a>
                            </li>
                        @endif

                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}" title="{{ __('S\'inscrire') }}">
                                    <i class="fas fa-user-plus"></i> {{-- Icône d'inscription --}}
                                </a>
                            </li>
                        @endif
                    @else
                        {{-- Menu déroulant pour l'utilisateur connecté --}}
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->name }}
                            </a>

                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('admin.dashboard') }}"> {{-- Lien vers le tableau de bord principal --}}
                                    <i class="fas fa-tachometer-alt me-2"></i> {{ __('Tableau de bord') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('profile.show') }}"> {{-- Lien vers le profil utilisateur --}}
                                    <i class="fas fa-user-circle me-2"></i> {{ __('Profil') }}
                                </a>
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
        </header>

        {{-- Wrapper principal pour le contenu des pages --}}
        <main class="whatsapp-content-wrapper">
            {{-- C'est ici que le contenu spécifique à chaque page sera injecté --}}
            @yield('content')
        </main>
    </div>

    {{-- Scripts Bootstrap 5.3.3 (via CDN) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    {{-- Section pour les scripts JavaScript spécifiques à la page --}}
    @yield('scripts')
</body>
</html>
