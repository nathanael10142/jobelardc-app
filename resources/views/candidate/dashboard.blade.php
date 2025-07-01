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
    <link href="{{ asset('css/user.css') }}" rel="stylesheet">

    {{-- Bootstrap 5.3.3 CSS (via CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    {{-- Font Awesome 6.5.2 (via CDN) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Styles spécifiques à la page --}}
    @stack('styles')
</head>
<body>
    <div id="app">
        {{-- En-tête principal --}}
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
                                <span class="d-none d-md-inline">{{ Auth::user()->name ?? 'Utilisateur' }}</span>
                                <i class="fas fa-ellipsis-v d-md-none"></i>
                            </a>

                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('profile.show') }}">
                                    <i class="fas fa-user me-2"></i> {{ __('Mon Profil') }}
                                </a>
                                {{-- Accès à la liste des annonces publiques --}}
                                <a class="dropdown-item" href="{{ route('listings.index') }}">
                                    <i class="fas fa-store me-2"></i> {{ __('Annonces du Marché') }}
                                </a>
                                {{-- Accès à ses demandes/offres (candidate/applications) --}}
                                <a class="dropdown-item" href="{{ route('candidate.applications.index') }}">
                                    <i class="fas fa-file-alt me-2"></i> {{ __('Mes Demandes/Offres') }}
                                </a>
                                
                                {{-- Groupes de discussion --}}
                                <a class="dropdown-item" href="{{ route('candidate.groups.index') }}">
                                    <i class="fas fa-users me-2"></i> {{ __('Groupes de discussion') }}
                                </a>
                                <hr class="dropdown-divider">
                                <a class="dropdown-item" href="{{ route('candidate.settings.index') }}">
                                    <i class="fas fa-cog me-2"></i> {{ __('Paramètres') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('candidate.payment.index') }}">
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

            {{-- Barre d'onglets (DISCUSSIONS, ACTUALITÉS, APPELS) --}}
            <nav class="whatsapp-tabs">
                <a href="{{ route('candidate.camera.index') }}" class="tab-item camera-icon" title="Ouvrir la caméra"><i class="fas fa-camera"></i></a>
                <a href="{{ route('candidate.chats.index') }}" class="tab-item" id="tab-chats">DISCUSSIONS</a>
                <a href="{{ route('candidate.status.index') }}" class="tab-item" id="tab-status">ACTUALITÉS</a>
                <a href="{{ route('candidate.calls.index') }}" class="tab-item" id="tab-calls">APPELS</a>
            </nav>
        </header>

        {{-- Contenu principal --}}
        <main class="whatsapp-content-wrapper">
            @yield('content')
        </main>
    </div>

    {{-- Scripts Bootstrap --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;

            function setActiveLink() {
                document.querySelectorAll('.whatsapp-tabs .tab-item').forEach(item => item.classList.remove('active'));
                document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => item.classList.remove('active'));

                if (currentPath === '{{ route('candidate.chats.index', [], false) }}') {
                    document.getElementById('tab-chats').classList.add('active');
                } else if (currentPath === '{{ route('candidate.status.index', [], false) }}') {
                    document.getElementById('tab-status').classList.add('active');
                } else if (currentPath === '{{ route('candidate.calls.index', [], false) }}') {
                    document.getElementById('tab-calls').classList.add('active');
                } else if (currentPath === '{{ route('candidate.camera.index', [], false) }}') {
                    document.querySelector('.camera-icon').classList.add('active');
                }

                document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
                    const itemPath = new URL(item.href).pathname;
                    if (itemPath === currentPath) {
                        item.classList.add('active');
                    }
                });
            }

            setActiveLink();
        });
    </script>

    {{-- Scripts spécifiques --}}
    @stack('scripts')
</body>
</html>
