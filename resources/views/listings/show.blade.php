{{-- resources/views/listings/show.blade.php --}}
@extends('layouts.user')

@section('title', $listing->title . ' - Détails de l\'Annonce - Jobela RDC')

@section('content')
@php
    use Illuminate\Support\Str;

    $avatar = $listing->user->profile_picture ?? null;
    $isExternal = $avatar && Str::startsWith($avatar, ['http://', 'https://']);
    $userName = $listing->user->name ?? $listing->posted_by_name ?? 'Anonyme';
    $postedByType = $listing->posted_by_type ?? 'N/A';

    $cleanedContactInfo = preg_replace('/\D/', '', $listing->contact_info ?? '');
    $isContactPhoneNumber = preg_match('/^\+?\d{8,}$/', $cleanedContactInfo);

    $profilePhoneNumber = $listing->user->phone_number ?? null;
    $cleanedProfilePhoneNumber = preg_replace('/\D/', '', $profilePhoneNumber ?? '');
    $isProfilePhoneNumberValid = $profilePhoneNumber && preg_match('/^\+?\d{8,}$/', $cleanedProfilePhoneNumber);
@endphp

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden listing-card">
        <div class="card-header d-flex align-items-center bg-whatsapp-green text-white py-3 px-4">
            <div class="me-3 flex-shrink-0">
                <a href="{{ route('profile.show', $listing->user->id) }}" class="text-white">
                    @if($avatar)
                        <img src="{{ $isExternal ? $avatar : asset('storage/' . $avatar) }}" alt="{{ $userName }}" class="avatar-header-lg">
                    @else
                        @php
                            $initials = collect(explode(' ', $userName))->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                            $colors = ['#FF5733', '#33FF57', '#3357FF', '#FF33A1', '#A133FF', '#33FFF3'];
                            $bgColor = $colors[($listing->user->id ?? 0) % count($colors)];
                        @endphp
                        <div class="avatar-header-lg d-flex justify-content-center align-items-center fw-bold text-uppercase" style="background-color: {{ $bgColor }};">
                            {{ $initials }}
                        </div>
                    @endif
                </a>
            </div>
            <div class="flex-grow-1">
                <h4 class="mb-0 text-truncate">{{ $listing->title }}</h4>
                <small class="text-white-75">
                    Publié {{ $listing->created_at->locale('fr')->diffForHumans() }} par
                    <strong class="fw-bold">{{ $userName }}</strong> <em class="opacity-75">({{ $postedByType }})</em>
                </small>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="info-item mb-3">
                <i class="fas fa-map-marker-alt me-2 text-whatsapp-secondary"></i>
                <strong class="text-dark">Localisation :</strong> {{ $listing->location }}
            </div>

            @if ($listing->salary)
                <div class="info-item mb-3">
                    <i class="fas fa-hand-holding-usd me-2 text-whatsapp-secondary"></i>
                    <strong class="text-dark">Salaire / Prix :</strong> {{ $listing->salary }}
                </div>
            @endif

            <hr class="my-4 border-whatsapp-light">

            <h5 class="fw-bold text-whatsapp-primary mb-3">Description :</h5>
            <p class="description-text mb-4">{{ $listing->description }}</p>

            <hr class="my-4 border-whatsapp-light">

            <div class="info-item mb-3">
                <strong class="text-dark">Type d'annonce :</strong>
                @if(isset($listing->is_job_offer) && $listing->is_job_offer)
                    <span class="badge bg-whatsapp-offer rounded-pill px-3 py-2 ms-2"><i class="fas fa-briefcase me-1"></i> Offre d'emploi</span>
                @else
                    <span class="badge bg-whatsapp-service rounded-pill px-3 py-2 ms-2"><i class="fas fa-tools me-1"></i> Demande de service</span>
                @endif
            </div>

            @if ($listing->contact_info)
                <div class="info-item mb-3">
                    <strong class="text-dark">Contact (info annonce) :</strong> {{ $listing->contact_info }}
                    @if ($isContactPhoneNumber)
                        <a href="https://wa.me/{{ $cleanedContactInfo }}?text=Bonjour,%20je%20suis%20intéressé(e)%20par%20votre%20annonce%20'{{ urlencode($listing->title) }}'." target="_blank" class="btn btn-whatsapp rounded-pill ms-2">
                            <i class="fab fa-whatsapp me-1"></i> Contacter via WhatsApp
                        </a>
                    @endif
                </div>
            @endif

            @auth
                @if ($listing->user && auth()->id() !== $listing->user->id)
                    <div class="info-item mb-3">
                        <strong class="text-dark">Démarrer une discussion :</strong>
                        <form id="createChatForm" action="{{ route('chats.createConversation') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="recipient_id" value="{{ $listing->user->id }}">
                            <button type="submit" class="btn btn-primary-jobela rounded-pill ms-2">
                                <i class="fas fa-comments me-1"></i> Envoyer un message sur Jobela
                            </button>
                        </form>
                    </div>
                @else
                    <p class="text-muted small mt-3">
                        <i class="fas fa-info-circle me-1"></i> Vous êtes le propriétaire de cette annonce. Vous ne pouvez pas démarrer de chat avec vous-même.
                    </p>
                @endif
            @endauth
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center p-4 bg-light rounded-bottom-4">
            <a href="{{ route('listings.index') }}" class="btn btn-outline-secondary-jobela rounded-pill d-flex align-items-center">
                <i class="fas fa-arrow-left me-2"></i> Retour aux annonces
            </a>

            @auth
                @if(auth()->id() === $listing->user_id || auth()->user()->hasAnyRole(['admin', 'super_admin']))
                    <div class="d-flex gap-2">
                        <a href="{{ route('listings.edit', $listing) }}" class="btn btn-warning rounded-pill d-flex align-items-center">
                            <i class="fas fa-edit me-2"></i> Modifier
                        </a>
                        <form action="{{ route('listings.destroy', $listing) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger rounded-pill d-flex align-items-center">
                                <i class="fas fa-trash-alt me-2"></i> Supprimer
                            </button>
                        </form>
                    </div>
                @endif
            @endauth
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    :root {
        --whatsapp-green: #008069;
        --whatsapp-light-green: #128C7E;
        --whatsapp-dark-green: #075E54;
        --whatsapp-bg-gray: #ECE5DD;
        --whatsapp-text-dark: #333;
        --whatsapp-text-secondary: #555;
        --whatsapp-light-gray: #CCC;
        --jobela-primary: #0d6efd; /* Example: Bootstrap primary blue */
        --jobela-secondary: #6c757d; /* Example: Bootstrap secondary gray */
        --jobela-offer-badge: #0d6efd; /* A blue for offers */
        --jobela-service-badge: #6c757d; /* A gray for services */
    }

    body {
        background-color: var(--whatsapp-bg-gray);
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
        color: var(--whatsapp-text-dark);
    }

    .container {
        max-width: 800px;
    }

    .listing-card {
        border: none;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
    }

    .card-header {
        background-color: var(--whatsapp-green) !important;
        padding: 1.5rem 2rem;
        border-bottom: none;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .card-header h4 {
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 0.25rem;
    }

    .card-header small {
        opacity: 0.9;
        font-size: 0.9rem;
    }

    .avatar-header-lg {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 255, 255, 0.8);
        background-color: #f0f0f0;
        color: #fff;
        font-size: 2.2rem;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .card-body {
        padding: 2rem;
    }

    .info-item {
        font-size: 1.1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }

    .info-item i {
        font-size: 1.3rem;
        margin-right: 0.75rem;
    }

    .text-whatsapp-secondary {
        color: var(--whatsapp-dark-green);
    }

    .text-whatsapp-primary {
        color: var(--whatsapp-dark-green);
    }

    .description-text {
        line-height: 1.8;
        font-size: 1.05rem;
        color: var(--whatsapp-text-dark);
    }

    .border-whatsapp-light {
        border-color: var(--whatsapp-light-gray) !important;
    }

    .badge {
        font-size: 0.95rem;
        padding: 0.5em 0.9em;
        font-weight: 600;
    }

    .bg-whatsapp-offer {
        background-color: var(--jobela-offer-badge) !important;
    }
    .bg-whatsapp-service {
        background-color: var(--jobela-service-badge) !important;
    }

    .btn-whatsapp {
        background-color: var(--whatsapp-light-green);
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        font-size: 0.95rem;
        transition: background-color 0.2s ease;
    }

    .btn-whatsapp:hover {
        background-color: var(--whatsapp-dark-green);
        color: white;
    }

    /* Renamed and restyled for chat button */
    .btn-primary-jobela { /* This is now your chat button style */
        background-color: var(--whatsapp-light-green); /* WhatsApp green */
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        font-size: 0.95rem;
        transition: background-color 0.2s ease;
    }
    .btn-primary-jobela:hover {
        background-color: var(--whatsapp-dark-green); /* Darker green on hover */
        color: white;
    }

    .btn-outline-secondary-jobela {
        color: var(--jobela-secondary);
        border-color: var(--jobela-secondary);
        padding: 0.6rem 1.2rem;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    .btn-outline-secondary-jobela:hover {
        background-color: var(--jobela-secondary);
        color: white;
    }

    .card-footer {
        background-color: var(--whatsapp-bg-gray) !important;
        padding: 1.5rem 2rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .btn.rounded-pill {
        border-radius: 50rem !important;
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .card-header {
            flex-direction: column;
            text-align: center;
            padding: 1rem 1.5rem;
            gap: 0.75rem;
        }
        .card-header .flex-grow-1 {
            text-align: center;
        }
        .avatar-header-lg {
            width: 70px;
            height: 70px;
            font-size: 1.8rem;
        }
        .card-footer {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem 1.5rem;
        }
        .card-footer .btn {
            width: 100%;
            justify-content: center;
        }
        .d-flex.gap-2 {
            width: 100%;
            flex-direction: column;
            gap: 0.75rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('createChatForm');

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault(); // Prevent the default form submission

                const formData = new FormData(form);
                const button = form.querySelector('button[type="submit"]');
                const originalButtonText = button.innerHTML; // Store original text

                button.disabled = true; // Disable button to prevent multiple clicks
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Création...'; // Show loading indicator

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest', // Important for Laravel to recognize AJAX
                        'Accept': 'application/json' // Tell server we expect JSON
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || 'Une erreur est survenue lors de la création de la discussion.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.redirect_to_existing_chat) {
                        window.location.href = data.redirect_to_existing_chat; // Redirect the browser
                    } else {
                        console.warn('Succès mais pas de redirection prévue ou données inattendues:', data);
                        alert(data.message || 'La discussion a été créée mais la redirection a échoué.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur: ' + error.message);
                })
                .finally(() => {
                    button.disabled = false; // Re-enable button
                    button.innerHTML = originalButtonText; // Restore button text
                });
            });
        }
    });
</script>
@endpush
