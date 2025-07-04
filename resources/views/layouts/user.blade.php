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
    {{-- <link href="{{ asset('css/user.css') }}" rel="stylesheet"> --}} {{-- Commenté car non fourni, assurez-vous qu'il existe ou retirez-le --}}

    {{-- Bootstrap 5.3.3 CSS (via CDN) - Base de la grille et des composants --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    {{-- Font Awesome 6.5.2 (via CDN) - Indispensable pour les icônes WhatsApp --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

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
                                {{-- Afficher le nom sur desktop, l'icône sur mobile --}}
                                <span class="d-none d-md-inline">{{ Auth::user()->name ?? 'Utilisateur' }}</span>
                                <i class="fas fa-ellipsis-v d-md-none"></i> {{-- Icône menu 3 points sur mobile --}}
                            </a>

                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                {{-- Liens spécifiques utilisateur --}}
                                <a class="dropdown-item" href="{{ route('profile.index') }}"> {{-- CORRECTED: Changed 'profile.show' to 'profile.index' --}}
                                    <i class="fas fa-user me-2"></i> {{ __('Mon Profil') }}
                                </a>
                                {{-- Nouvelle entrée pour les Annonces du Marché --}}
                                <a class="dropdown-item" href="{{ route('listings.index') }}">
                                    <i class="fas fa-store me-2"></i> {{ __('Annonces du Marché') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('applications.index') }}"> {{-- Assumed route for applications --}}
                                    <i class="fas fa-file-alt me-2"></i> {{ __('Mes Demandes/Offres') }}
                                </a>

                                <a class="dropdown-item" href="{{ route('groups.index') }}"> {{-- Assumed route for groups --}}
                                    <i class="fas fa-users me-2"></i> {{ __('Groupes de discussion') }}
                                </a>
                                <hr class="dropdown-divider">
                                {{-- Nouveaux liens : Paramètres et Paiement --}}
                                <a class="dropdown-item" href="{{ route('settings.index') }}"> {{-- Assumed route for settings --}}
                                    <i class="fas fa-cog me-2"></i> {{ __('Paramètres') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('payment.index') }}"> {{-- Assumed route for payment --}}
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
                {{-- Changed data-target-content to href for actual routes, where applicable --}}
                <a href="{{ route('camera.index') }}" class="tab-item camera-icon" title="Ouvrir la caméra"><i class="fas fa-camera"></i></a> {{-- Assumed camera route --}}
                <a href="{{ route('chats.index') }}" class="tab-item" id="tab-chats">DISCUSSIONS</a> {{-- Assumed chats route --}}
                <a href="{{ route('status.index') }}" class="tab-item" id="tab-status">ACTUALITÉS</a> {{-- Assumed status route --}}
                <a href="{{ route('calls.index') }}" class="tab-item" id="tab-calls">APPELS</a> {{-- Assumed calls route --}}
            </nav>
        </header>

        {{-- Wrapper principal pour le contenu des pages (le contenu réel des onglets) --}}
        <main class="whatsapp-content-wrapper">
            {{-- This is the key change: @yield('content') will pull in the specific view's content --}}
            @yield('content')
        </main>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDxOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;

            // Function to set active class based on URL
            function setActiveLink() {
                // Remove active from all tabs and dropdown items first
                document.querySelectorAll('.whatsapp-tabs .tab-item').forEach(item => item.classList.remove('active'));
                document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => item.classList.remove('active'));

                // Set active for tab items
                if (currentPath === '{{ route('chats.index', [], false) }}') { // Ensure non-absolute path for comparison
                    document.getElementById('tab-chats').classList.add('active');
                } else if (currentPath === '{{ route('status.index', [], false) }}') {
                    document.getElementById('tab-status').classList.add('active');
                } else if (currentPath === '{{ route('calls.index', [], false) }}') {
                    document.getElementById('tab-calls').classList.add('active');
                } else if (currentPath === '{{ route('camera.index', [], false) }}') {
                     document.querySelector('.camera-icon').classList.add('active');
                } else if (currentPath === '{{ route('home', [], false) }}' || currentPath === '{{ route('listings.index', [], false) }}') {
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
