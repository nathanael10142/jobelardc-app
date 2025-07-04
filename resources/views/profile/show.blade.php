@extends('layouts.user')

@section('title', 'Mon Profil - Jobela RDC')

@section('content')
@php
    use Illuminate\Support\Str;

    $user = auth()->user();
    $avatarPath = $user->profile_picture ?? null;
    $isExternal = $avatarPath && Str::startsWith($avatarPath, ['http://', 'https://']);
    $avatarHtml = '';

    if ($avatarPath) {
        $avatarSrc = $isExternal ? $avatarPath : asset('storage/' . $avatarPath);
        $avatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil de ' . $user->name . '" class="profile-avatar-thumbnail">';
    } else {
        // Fallback to initials avatar if no profile picture
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
        // Generate a consistent color based on user's email or ID
        $bgColor = '#' . substr(md5($user->email ?? $user->id ?? uniqid()), 0, 6);
        $avatarHtml = '<div class="profile-avatar-placeholder" style="background-color: ' . $bgColor . ';">' . $initials . '</div>';
    }
@endphp

<div class="content-section p-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <div class="card whatsapp-profile-card shadow-sm">
        <div class="card-header d-flex align-items-center bg-whatsapp-green text-white rounded-top">
            <div class="me-3 profile-avatar-container">
                {!! $avatarHtml !!}
            </div>
            <div class="profile-header-info">
                <h4 class="mb-0">{{ $user->name }}</h4>
                <small class="text-white-75">Membre depuis {{ $user->created_at->locale('fr')->translatedFormat('F Y') }}</small>
            </div>
        </div>

        <div class="card-body profile-details-body">
            <div class="profile-detail-item">
                <i class="fas fa-envelope me-3 text-whatsapp-muted"></i>
                <div class="detail-content">
                    <strong>Email</strong>
                    <p class="mb-0">{{ $user->email }}</p>
                </div>
            </div>

            @if($user->phone_number)
                <div class="profile-detail-item">
                    <i class="fas fa-phone-alt me-3 text-whatsapp-muted"></i>
                    <div class="detail-content">
                        <strong>Téléphone</strong>
                        <p class="mb-0">{{ $user->phone_number }}</p>
                    </div>
                </div>
            @endif

            @if($user->location)
                <div class="profile-detail-item">
                    <i class="fas fa-map-marker-alt me-3 text-whatsapp-muted"></i>
                    <div class="detail-content">
                        <strong>Localisation</strong>
                        <p class="mb-0">{{ $user->location }}</p>
                    </div>
                </div>
            @endif

            @if($user->user_type)
                <div class="profile-detail-item">
                    <i class="fas fa-user-tag me-3 text-whatsapp-muted"></i>
                    <div class="detail-content">
                        <strong>Type d'utilisateur</strong>
                        <p class="mb-0">{{ ucfirst($user->user_type) }}</p>
                    </div>
                </div>
            @endif

            @if($user->bio)
                <hr class="my-4">
                <div class="profile-detail-item align-items-start">
                    <i class="fas fa-info-circle me-3 text-whatsapp-muted mt-1"></i>
                    <div class="detail-content flex-grow-1">
                        <strong>À propos</strong>
                        <p class="mb-0">{{ $user->bio }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="card-footer text-end p-3">
            <a href="{{ route('profile.edit') }}" class="btn btn-whatsapp-primary rounded-pill px-4 py-2">
                <i class="fas fa-edit me-2"></i> Modifier mon profil
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* WhatsApp Colors and Variables */
    :root {
        --whatsapp-green-dark: #075E54;
        --whatsapp-green-light: #128C7E;
        --whatsapp-blue-seen: #34B7F1;
        --whatsapp-background: #E5DDD5; /* Light background for the page */
        --whatsapp-text-dark: #202C33;
        --whatsapp-text-muted: #667781;
        --whatsapp-border: #E0E0E0; /* Lighter border for cards */
        --whatsapp-card-bg: #FFFFFF; /* White background for cards */
        --whatsapp-primary-button: #25D366; /* A vibrant green for primary actions */
    }

    body {
        background-color: var(--whatsapp-background);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--whatsapp-text-dark);
    }

    .content-section {
        max-width: 800px;
        margin: 20px auto; /* Centrer et donner de l'espace */
        padding: 0; /* Le padding est déjà dans les éléments internes */
    }

    .whatsapp-profile-card {
        border: none; /* Supprime la bordure par défaut de Bootstrap */
        border-radius: 12px;
        overflow: hidden; /* Assure que les coins arrondis sont respectés */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Ombre plus prononcée */
    }

    .whatsapp-profile-card .card-header {
        background-color: var(--whatsapp-green-dark) !important;
        color: white;
        padding: 1.5rem; /* Augmente le padding */
        display: flex;
        align-items: center;
        gap: 1rem;
        border-bottom: none; /* Supprime la bordure du header */
    }

    .profile-avatar-container {
        flex-shrink: 0; /* Empêche l'avatar de rétrécir */
    }

    .profile-avatar-thumbnail,
    .profile-avatar-placeholder {
        width: 80px; /* Taille plus grande pour l'avatar de profil */
        height: 80px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid var(--whatsapp-green-light); /* Bordure verte plus épaisse */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem; /* Taille de police pour les initiales */
        font-weight: bold;
        color: white;
        text-transform: uppercase;
        background-color: #ccc; /* Fallback */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Ombre pour l'avatar */
    }

    .profile-header-info h4 {
        font-weight: 700;
        font-size: 1.8rem; /* Taille de nom plus grande */
    }

    .profile-header-info small {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.85);
    }

    .profile-details-body {
        padding: 1.5rem;
        background-color: var(--whatsapp-card-bg);
    }

    .profile-detail-item {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--whatsapp-border); /* Séparateur léger */
    }
    .profile-detail-item:last-of-type {
        border-bottom: none; /* Pas de bordure pour le dernier élément */
    }

    .profile-detail-item i {
        font-size: 1.2rem; /* Taille des icônes */
        color: var(--whatsapp-text-muted);
        min-width: 30px; /* Assure un alignement des icônes */
        text-align: center;
    }

    .profile-detail-item .detail-content {
        flex-grow: 1;
    }

    .profile-detail-item strong {
        display: block; /* Force le titre à sa propre ligne */
        font-size: 0.85rem;
        color: var(--whatsapp-text-muted);
        margin-bottom: 2px;
    }

    .profile-detail-item p {
        font-size: 1rem;
        color: var(--whatsapp-text-dark);
        line-height: 1.4;
    }

    .profile-details-body hr {
        border-top: 1px solid var(--whatsapp-border);
        margin-top: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .whatsapp-profile-card .card-footer {
        background-color: var(--whatsapp-card-bg); /* Même fond que le corps */
        border-top: 1px solid var(--whatsapp-border);
        padding: 1rem 1.5rem;
    }

    .btn-whatsapp-primary {
        background-color: var(--whatsapp-primary-button);
        border-color: var(--whatsapp-primary-button);
        color: white;
        font-weight: 600;
        transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.1s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .btn-whatsapp-primary:hover {
        background-color: var(--whatsapp-green-light);
        border-color: var(--whatsapp-green-light);
        color: white;
        transform: translateY(-1px); /* Léger effet de soulèvement */
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .content-section {
            margin: 0; /* Pas de marge sur les côtés sur mobile */
            padding: 0;
        }

        .whatsapp-profile-card {
            border-radius: 0; /* Plein écran sur mobile */
            box-shadow: none;
            min-height: 100vh; /* Prend toute la hauteur disponible */
        }

        .whatsapp-profile-card .card-header {
            padding: 1rem;
            flex-direction: column; /* Empile l'avatar et le nom sur mobile */
            text-align: center;
        }

        .profile-avatar-thumbnail,
        .profile-avatar-placeholder {
            width: 70px;
            height: 70px;
            font-size: 2rem;
            margin-bottom: 10px; /* Espacement sous l'avatar */
        }

        .profile-header-info h4 {
            font-size: 1.5rem;
        }

        .profile-header-info small {
            font-size: 0.8rem;
        }

        .profile-details-body {
            padding: 1rem;
        }

        .profile-detail-item {
            margin-bottom: 0.8rem;
            padding: 0.4rem 0;
        }

        .profile-detail-item i {
            font-size: 1rem;
            min-width: 25px;
        }

        .profile-detail-item strong {
            font-size: 0.8rem;
        }

        .profile-detail-item p {
            font-size: 0.9rem;
        }

        .whatsapp-profile-card .card-footer {
            padding: 1rem;
            text-align: center !important; /* Centrer le bouton sur mobile */
        }

        .btn-whatsapp-primary {
            width: 100%; /* Bouton pleine largeur */
            padding: 0.8rem 1rem;
            font-size: 1rem;
        }
    }
</style>
@endpush
