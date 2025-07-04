@extends('layouts.user')

@section('title', 'Modifier mon profil - Jobela RDC')

@section('content')
@php
    use Illuminate\Support\Str;

    $user = auth()->user();
    $avatarPath = $user->profile_picture ?? null;
    $isExternal = $avatarPath && Str::startsWith($avatarPath, ['http://', 'https://']);
    $currentAvatarHtml = '';

    if ($avatarPath) {
        $avatarSrc = $isExternal ? $avatarPath : asset('storage/' . $avatarPath);
        $currentAvatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil actuelle" class="edit-profile-avatar-thumbnail">';
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
        $currentAvatarHtml = '<div class="edit-profile-avatar-placeholder" style="background-color: ' . $bgColor . ';">' . $initials . '</div>';
    }
@endphp

<div class="content-section p-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <div class="card whatsapp-edit-profile-card shadow-sm">
        <div class="card-header bg-whatsapp-green text-white d-flex justify-content-between align-items-center rounded-top">
            <h5 class="mb-0">Modifier mon profil</h5>
            <a href="{{ route('profile.show') }}" class="btn btn-sm btn-light text-whatsapp-dark rounded-pill">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card-body">
                {{-- Avatar --}}
                <div class="mb-4 text-center">
                    {!! $currentAvatarHtml !!}
                    <div class="mt-3">
                        <label for="profile_picture" class="btn btn-whatsapp-secondary rounded-pill px-4 py-2">
                            <i class="fas fa-camera me-2"></i> Changer la photo
                        </label>
                        <input type="file" name="profile_picture" id="profile_picture" class="d-none @error('profile_picture') is-invalid @enderror">
                        @error('profile_picture')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Nom --}}
                <div class="mb-3 form-group-whatsapp">
                    <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Téléphone --}}
                <div class="mb-3 form-group-whatsapp">
                    <label for="phone_number" class="form-label">Téléphone</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}"
                        class="form-control @error('phone_number') is-invalid @enderror">
                    @error('phone_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Localisation --}}
                <div class="mb-3 form-group-whatsapp">
                    <label for="location" class="form-label">Localisation</label>
                    <input type="text" name="location" value="{{ old('location', $user->location) }}"
                        class="form-control @error('location') is-invalid @enderror">
                    @error('location')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Bio --}}
                <div class="mb-3 form-group-whatsapp">
                    <label for="bio" class="form-label">Biographie / À propos</label>
                    <textarea name="bio" rows="4" class="form-control @error('bio') is-invalid @enderror">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="card-footer text-end p-3 bg-whatsapp-light-gray border-top-0">
                <button type="submit" class="btn btn-whatsapp-primary rounded-pill px-4 py-2">
                    <i class="fas fa-save me-2"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
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
        --whatsapp-secondary-button: #E0E0E0; /* Light gray for secondary actions */
        --whatsapp-light-gray: #F0F2F5; /* Background for footer */
    }

    body {
        background-color: var(--whatsapp-background);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--whatsapp-text-dark);
    }

    .content-section {
        max-width: 800px;
        margin: 20px auto; /* Centrer et donner de l'espace */
        padding: 0;
    }

    .whatsapp-edit-profile-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .whatsapp-edit-profile-card .card-header {
        background-color: var(--whatsapp-green-dark) !important;
        color: white;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: none;
    }

    .whatsapp-edit-profile-card .card-header h5 {
        font-weight: 600;
        font-size: 1.25rem;
    }

    .btn-light.text-whatsapp-dark {
        background-color: #fff;
        color: var(--whatsapp-text-dark) !important;
        border: 1px solid var(--whatsapp-border);
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .btn-light.text-whatsapp-dark:hover {
        background-color: var(--whatsapp-light-hover);
        color: var(--whatsapp-text-dark) !important;
    }

    /* Avatar section */
    .edit-profile-avatar-thumbnail,
    .edit-profile-avatar-placeholder {
        width: 100px; /* Taille de l'avatar */
        height: 100px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid var(--whatsapp-green-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem; /* Taille de police pour les initiales */
        font-weight: bold;
        color: white;
        text-transform: uppercase;
        background-color: #ccc; /* Fallback */
        margin: 0 auto; /* Centrer l'avatar */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .btn-whatsapp-secondary {
        background-color: var(--whatsapp-secondary-button);
        border-color: var(--whatsapp-secondary-button);
        color: var(--whatsapp-text-dark);
        font-weight: 600;
        transition: background-color 0.2s ease, transform 0.1s ease;
    }
    .btn-whatsapp-secondary:hover {
        background-color: #d0d0d0; /* Slightly darker on hover */
        transform: translateY(-1px);
    }

    /* Form fields */
    .form-group-whatsapp .form-label {
        font-weight: 600;
        color: var(--whatsapp-text-dark);
        margin-bottom: 0.5rem;
    }

    .form-group-whatsapp .form-control {
        border-radius: 8px; /* Coins légèrement arrondis */
        border: 1px solid var(--whatsapp-border);
        padding: 0.75rem 1rem;
        font-size: 1rem;
        color: var(--whatsapp-text-dark);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-group-whatsapp .form-control:focus {
        border-color: var(--whatsapp-green-light);
        box-shadow: 0 0 0 0.25rem rgba(18, 140, 126, 0.25); /* Ombre de focus verte */
        outline: none;
    }

    .form-group-whatsapp .invalid-feedback {
        font-size: 0.85rem;
        margin-top: 0.25rem;
    }

    /* Footer and Save button */
    .whatsapp-edit-profile-card .card-footer {
        background-color: var(--whatsapp-light-gray);
        border-top: 1px solid var(--whatsapp-border);
        padding: 1.5rem;
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
        transform: translateY(-1px);
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .content-section {
            margin: 0;
            padding: 0;
        }

        .whatsapp-edit-profile-card {
            border-radius: 0;
            box-shadow: none;
            min-height: 100vh;
        }

        .whatsapp-edit-profile-card .card-header {
            padding: 1rem;
        }

        .whatsapp-edit-profile-card .card-header h5 {
            font-size: 1.1rem;
        }

        .btn-light.text-whatsapp-dark {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        .edit-profile-avatar-thumbnail,
        .edit-profile-avatar-placeholder {
            width: 80px;
            height: 80px;
            font-size: 2rem;
        }

        .btn-whatsapp-secondary {
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
        }

        .form-group-whatsapp .form-label {
            font-size: 0.9rem;
        }

        .form-group-whatsapp .form-control {
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
        }

        .whatsapp-edit-profile-card .card-footer {
            padding: 1rem;
            text-align: center !important;
        }

        .btn-whatsapp-primary {
            width: 100%;
            padding: 0.8rem 1rem;
            font-size: 1rem;
        }
    }
</style>
@endpush
