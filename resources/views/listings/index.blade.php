{{-- resources/views/listings/index.blade.php --}}
@extends('layouts.user')

@section('title', 'Annonces d\'Emploi et Services - Jobela RDC')

@section('content')
@php
    use Illuminate\Support\Str;
@endphp

<div class="content-section p-3" id="main-listings-content">
    <h5 class="mb-3 whatsapp-heading">
        <i class="fas fa-bullhorn me-2"></i> Annonces du Marché (Emplois & Services)
    </h5>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        {{-- Zone de recherche WhatsApp --}}
        <form action="{{ route('listings.index') }}" method="GET" class="whatsapp-search-form flex-grow-1 me-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control whatsapp-search-input" placeholder="{{ __('Rechercher annonces...') }}" value="{{ request('search') }}">
                <button class="btn whatsapp-search-btn" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>

        <a href="{{ route('listings.create') }}" class="btn btn-whatsapp-primary rounded-pill px-4 shadow-sm flex-shrink-0">
            <i class="fas fa-plus-circle me-2"></i> Publier une annonce
        </a>
    </div>

    {{-- Liste des annonces --}}
    <div class="listings-container">
        @forelse ($jobListings as $listing)
            <div class="card listing-card mb-3 shadow-sm">
                <div class="card-body d-flex align-items-start p-3">
                    {{-- Avatar --}}
                    <div class="me-3 flex-shrink-0">
                        @php
                            $user = $listing->user;
                            $avatarHtml = '';

                            if ($user) {
                                $avatarPath = $user->profile_picture ?? null;
                                $isExternal = $avatarPath && Str::startsWith($avatarPath, ['http://', 'https://']);

                                if ($avatarPath) {
                                    $avatarSrc = $isExternal ? $avatarPath : asset('storage/' . $avatarPath);
                                    $avatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil de ' . $user->name . '" class="avatar-thumbnail">';
                                } else {
                                    // Fallback to initials avatar if no profile picture
                                    $avatarHtml = '<div class="avatar-text-placeholder" style="background-color: ' . ($user->avatar_bg_color ?? '#777') . ';">' . ($user->initials ?? '??') . '</div>';
                                }
                            } else {
                                // Fallback for anonymous or deleted user
                                $avatarHtml = '<div class="avatar-text-placeholder" style="background-color: #999;"><i class="fas fa-user-circle"></i></div>';
                            }
                        @endphp
                        {!! $avatarHtml !!}
                    </div>

                    {{-- Contenu principal de l'annonce --}}
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 profile-name text-truncate">
                                {{ $user->name ?? $listing->posted_by_name ?? 'Anonyme' }}
                            </h6>
                            <small class="text-muted listing-time">
                                {{ $listing->created_at->locale('fr')->diffForHumans() }}
                            </small>
                        </div>
                        <p class="text-muted small mb-2 listing-type">{{ $listing->posted_by_type ?? 'Type non spécifié' }}</p>

                        <h5 class="card-title mt-2">{{ $listing->title }}</h5>
                        <p class="card-text mb-1 listing-location">
                            <i class="fas fa-map-marker-alt me-1 text-whatsapp-muted"></i> {{ $listing->location }}
                        </p>
                        @if ($listing->salary)
                            <p class="card-text mb-1 listing-salary">
                                <i class="fas fa-hand-holding-usd me-1 text-whatsapp-muted"></i> {{ $listing->salary }}
                            </p>
                        @endif

                        <a href="{{ route('listings.show', $listing->id) }}"
                           class="btn btn-sm btn-outline-whatsapp-green mt-3 rounded-pill">
                            <i class="fas fa-eye me-1"></i> Voir Détails
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info text-center whatsapp-card" role="alert">
                Aucune annonce disponible pour le moment. Soyez le premier à poster !
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('styles')
<style>
    /* WhatsApp Colors and Variables (ensure these are defined globally or here) */
    :root {
        --whatsapp-green-dark: #075E54;
        --whatsapp-green-light: #128C7E;
        --whatsapp-blue-seen: #34B7F1;
        --whatsapp-background: #E5DDD5; /* Light background for the page */
        --whatsapp-chat-bg: #E5DDD5;
        --whatsapp-message-sent: #DCF8C6;
        --whatsapp-message-received: #FFFFFF;
        --whatsapp-text-dark: #202C33;
        --whatsapp-text-muted: #667781;
        --whatsapp-border: #E0E0E0; /* Lighter border for cards */
        --whatsapp-card-bg: #FFFFFF; /* White background for cards */
        --whatsapp-light-hover: #F0F0F0;
        --whatsapp-primary-button: #25D366; /* A vibrant green for primary actions */
        --whatsapp-search-bg: #F0F2F5; /* Background for search input */
        --whatsapp-search-border: #D1D7DA; /* Border for search input */
        --whatsapp-icon-color: #667781; /* Color for search icon */
    }

    body {
        background-color: var(--whatsapp-background);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* A more common system font */
        color: var(--whatsapp-text-dark);
    }

    .content-section {
        max-width: 800px;
        margin: 0 auto;
        padding-top: 20px !important;
    }

    .whatsapp-heading {
        color: var(--whatsapp-green-dark);
        font-weight: 700;
        display: flex;
        align-items: center;
        margin-bottom: 25px !important;
    }

    /* WhatsApp Search Bar Styles */
    .whatsapp-search-form {
        border-radius: 20px; /* Highly rounded */
        overflow: hidden; /* Ensure content respects border-radius */
        background-color: var(--whatsapp-search-bg);
        border: 1px solid var(--whatsapp-search-border);
    }

    .whatsapp-search-input {
        background-color: transparent; /* No background for input itself */
        border: none;
        box-shadow: none !important; /* Remove focus shadow */
        padding: 0.5rem 1rem;
        color: var(--whatsapp-text-dark);
        border-radius: 20px 0 0 20px; /* Only left side rounded */
    }

    .whatsapp-search-input::placeholder {
        color: var(--whatsapp-text-muted);
        opacity: 0.7;
    }

    .whatsapp-search-input:focus {
        border-color: transparent; /* No border on focus */
        box-shadow: none; /* No shadow on focus */
    }

    .whatsapp-search-btn {
        background-color: transparent; /* No background for button itself */
        border: none;
        color: var(--whatsapp-icon-color);
        padding: 0.5rem 1rem;
        border-radius: 0 20px 20px 0; /* Only right side rounded */
        transition: color 0.2s ease;
    }

    .whatsapp-search-btn:hover {
        color: var(--whatsapp-green-dark); /* Darker green on hover */
    }


    .btn-whatsapp-primary {
        background-color: var(--whatsapp-primary-button);
        border-color: var(--whatsapp-primary-button);
        color: white;
        font-weight: 600;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .btn-whatsapp-primary:hover {
        background-color: var(--whatsapp-green-light);
        border-color: var(--whatsapp-green-light);
        color: white;
    }

    .listings-container {
        padding: 5px; /* Slight padding for the container */
    }

    .listing-card {
        background-color: var(--whatsapp-card-bg);
        border: 1px solid var(--whatsapp-border);
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .listing-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08); /* Slightly more pronounced shadow */
    }

    .avatar-thumbnail, .avatar-text-placeholder {
        width: 55px; /* Slightly larger avatar */
        height: 55px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--whatsapp-green-light); /* Green border around avatar */
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem; /* For initials/icon */
        font-weight: bold;
        color: white;
        text-transform: uppercase;
        background-color: #ccc; /* Default background for initials */
    }

    .avatar-text-placeholder i {
        font-size: 1.8rem; /* Icon size for default avatar */
    }

    .profile-name {
        font-weight: 600;
        color: var(--whatsapp-green-dark);
        font-size: 1.05rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%; /* Ensures name fits */
    }

    .listing-time {
        font-size: 0.8rem;
        color: var(--whatsapp-text-muted);
    }

    .listing-type {
        font-weight: 500; /* Make type slightly bolder */
        color: var(--whatsapp-text-muted);
    }

    .card-title {
        font-size: 1.25rem; /* Larger and more prominent title */
        font-weight: 700;
        color: var(--whatsapp-text-dark); /* Ensure it's readable */
        margin-bottom: 0.5rem;
    }

    .listing-location, .listing-salary {
        font-size: 0.95rem;
        color: var(--whatsapp-text-muted);
        display: flex;
        align-items: center;
    }

    .listing-location i, .listing-salary i {
        color: var(--whatsapp-text-muted); /* Icon color */
    }

    .btn-outline-whatsapp-green {
        color: var(--whatsapp-green-light);
        border-color: var(--whatsapp-green-light);
        font-weight: 600;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }

    .btn-outline-whatsapp-green:hover {
        background-color: var(--whatsapp-green-light);
        color: white;
        border-color: var(--whatsapp-green-light);
    }

    .alert-info.whatsapp-card { /* Style for empty state alert */
        background-color: var(--whatsapp-message-received);
        border-color: var(--whatsapp-border);
        color: var(--whatsapp-text-dark);
        border-radius: 12px;
        padding: 1.5rem;
    }

    /* Responsive adjustments */
    @media (max-width: 767px) {
        .content-section {
            padding: 10px;
        }

        .whatsapp-heading {
            font-size: 1.2rem;
            margin-bottom: 15px !important;
        }

        .whatsapp-search-form {
            margin-right: 1rem !important; /* Adjust margin for smaller screens */
        }

        .btn-whatsapp-primary {
            padding: 0.6rem 1rem !important; /* Adjust padding for smaller button */
            font-size: 0.9rem !important;
            white-space: nowrap; /* Prevent button text from wrapping */
        }

        .avatar-thumbnail, .avatar-text-placeholder {
            width: 45px;
            height: 45px;
            font-size: 1.3rem;
        }

        .avatar-text-placeholder i {
            font-size: 1.6rem;
        }

        .profile-name {
            font-size: 0.9rem;
        }

        .listing-time {
            font-size: 0.75rem;
        }

        .listing-type {
            font-size: 0.8rem;
        }

        .card-title {
            font-size: 1.1rem;
        }

        .listing-location, .listing-salary {
            font-size: 0.85rem;
        }

        .btn-outline-whatsapp-green {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
    }
</style>
@endpush
