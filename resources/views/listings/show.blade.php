@extends('layouts.user')

@section('title', $listing->title . ' - Détails de l\'Annonce - Jobela RDC')

@section('content')
@php
    use Illuminate\Support\Str;

    $user = $listing->user;
    $avatarHtml = '';

    if ($user) {
        $avatarPath = $user->profile_picture ?? null;
        $isExternal = $avatarPath && Str::startsWith($avatarPath, ['http://', 'https://']);

        if ($avatarPath) {
            $avatarSrc = $isExternal ? $avatarPath : asset('storage/' . $avatarPath);
            $avatarHtml = '<img src="' . $avatarSrc . '" alt="Photo de profil de ' . $user->name . '" class="avatar-header-lg">';
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
            // Générer une couleur cohérente basée sur l'email ou l'ID de l'utilisateur
            $bgColor = '#' . substr(md5($user->email ?? $user->id ?? uniqid()), 0, 6);
            $avatarHtml = '<div class="avatar-header-lg d-flex justify-content-center align-items-center fw-bold text-uppercase" style="background-color: ' . $bgColor . ';">' . $initials . '</div>';
        }
    } else {
        // Fallback for anonymous or deleted user
        $avatarHtml = '<div class="avatar-header-lg d-flex justify-content-center align-items-center fw-bold text-uppercase" style="background-color: #999;"><i class="fas fa-user-circle"></i></div>';
    }

    $userName = $listing->user->name ?? $listing->posted_by_name ?? 'Anonyme';
    $postedByType = $listing->is_job_offer ? 'Employeur' : 'Prestataire'; // Assuming these types based on is_job_offer

    $cleanedContactInfo = preg_replace('/\D/', '', $listing->contact_info ?? '');
    $isContactPhoneNumber = preg_match('/^\+?\d{8,}$/', $cleanedContactInfo);
@endphp

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show whatsapp-alert" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card whatsapp-card shadow-lg border-0 rounded-4 overflow-hidden listing-card">
        <div class="card-header d-flex align-items-center bg-whatsapp-green text-white py-3 px-4">
            <div class="me-3 flex-shrink-0">
                <a href="{{ $listing->user ? route('profile.show', $listing->user->id) : '#' }}" class="text-white">
                    {!! $avatarHtml !!}
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
                <i class="fas fa-map-marker-alt me-2 text-whatsapp-muted"></i>
                <strong class="text-dark">Localisation :</strong> {{ $listing->location }}
            </div>

            @if ($listing->salary)
                <div class="info-item mb-3">
                    <i class="fas fa-hand-holding-usd me-2 text-whatsapp-muted"></i>
                    <strong class="text-dark">Salaire / Prix :</strong> {{ $listing->salary }}
                </div>
            @endif

            <hr class="my-4 border-whatsapp-light">

            <h5 class="fw-bold text-whatsapp-dark-green mb-3">Description :</h5>
            <p class="description-text mb-4">{{ $listing->description }}</p>

            <hr class="my-4 border-whatsapp-light">

            <div class="info-item mb-3">
                <strong class="text-dark">Type d'annonce :</strong>
                @if(isset($listing->is_job_offer) && $listing->is_job_offer)
                    <span class="badge bg-whatsapp-offer rounded-pill px-3 py-2 ms-2"><i class="fas fa-briefcase me-1"></i> Offre d'emploi</span>
                @else
                    <span class="badge bg-whatsapp-service rounded-pill px-3 py-2 ms-2"><i class="fas fa-tools me-1"></i> Offre de service</span>
                @endif
            </div>

            @if ($listing->contact_info)
                <div class="info-item mb-3 flex-wrap align-items-center"> {{-- Added flex-wrap --}}
                    <strong class="text-dark me-2 mb-2 mb-sm-0">Contact (info annonce) :</strong>
                    <span class="contact-text">{{ $listing->contact_info }}</span>
                    @if ($isContactPhoneNumber)
                        <a href="https://wa.me/{{ $cleanedContactInfo }}?text=Bonjour,%20je%20suis%20intéressé(e)%20par%20votre%20annonce%20'{{ urlencode($listing->title) }}'." target="_blank" class="btn btn-whatsapp rounded-pill ms-sm-2 mt-2 mt-sm-0">
                            <i class="fab fa-whatsapp me-1"></i> Contacter via WhatsApp
                        </a>
                    @endif
                </div>
            @endif

            @auth
                @if ($listing->user && auth()->id() !== $listing->user->id)
                    <div class="info-item mb-3 flex-wrap align-items-center"> {{-- Added flex-wrap --}}
                        <strong class="text-dark me-2 mb-2 mb-sm-0">Démarrer une discussion :</strong>
                        <form id="createChatForm" action="{{ route('chats.createConversation') }}" method="POST" class="d-inline-flex flex-wrap"> {{-- Changed to d-inline-flex flex-wrap --}}
                            @csrf
                            <input type="hidden" name="recipient_id" value="{{ $listing->user->id }}">
                            <button type="submit" class="btn btn-whatsapp-chat rounded-pill mt-2 mt-sm-0">
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

        <div class="card-footer d-flex justify-content-between align-items-center p-4 bg-whatsapp-light-gray rounded-bottom-4 flex-wrap"> {{-- Added flex-wrap --}}
            <a href="{{ route('listings.index') }}" class="btn btn-outline-whatsapp-secondary rounded-pill d-flex align-items-center mb-2 mb-md-0">
                <i class="fas fa-arrow-left me-2"></i> Retour aux annonces
            </a>

            @auth
                @if(auth()->id() === $listing->user_id || auth()->user()->hasAnyRole(['admin', 'super_admin']))
                    <div class="d-flex gap-2 flex-wrap justify-content-end"> {{-- Added flex-wrap and justify-content-end --}}
                        <a href="{{ route('listings.edit', $listing) }}" class="btn btn-whatsapp-warning rounded-pill d-flex align-items-center mb-2 mb-sm-0">
                            <i class="fas fa-edit me-2"></i> Modifier
                        </a>
                        <form action="{{ route('listings.destroy', $listing) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.');" class="d-flex"> {{-- Added d-flex for button alignment --}}
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-whatsapp-danger rounded-pill d-flex align-items-center">
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" xintegrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    :root {
        --whatsapp-green-dark: #075E54;
        --whatsapp-green-light: #128C7E;
        --whatsapp-blue-seen: #34B7F1;
        --whatsapp-background: #E5DDD5; /* Light background for the page */
        --whatsapp-text-dark: #202C33;
        --whatsapp-text-muted: #667781;
        --whatsapp-border: #E0E0E0; /* Lighter border for cards */
        --whatsapp-card-bg: #FFFFFF; /* White background for cards */
        --whatsapp-light-hover: #F0F0F0;
        --whatsapp-primary-button: #25D366; /* A vibrant green for primary actions */
        --whatsapp-light-gray: #F0F2F5; /* Background for footer */
        --whatsapp-offer-badge: #0d6efd; /* Bootstrap primary blue, for job offers */
        --whatsapp-service-badge: #6c757d; /* Bootstrap secondary gray, for service offers */
        --whatsapp-warning-button: #ffc107; /* Bootstrap warning yellow */
        --whatsapp-danger-button: #dc3545; /* Bootstrap danger red */
    }

    body {
        background-color: var(--whatsapp-background);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--whatsapp-text-dark);
    }

    .container {
        max-width: 800px;
    }

    .whatsapp-card {
        border: none;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
    }

    .card-header {
        background-color: var(--whatsapp-green-dark) !important; /* Darker green for header */
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
        color: rgba(255, 255, 255, 0.85); /* Lighter text for small */
    }

    .avatar-header-lg {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 255, 255, 0.8);
        background-color: #f0f0f0; /* Fallback for image */
        color: #fff;
        font-size: 2.2rem;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-shrink: 0;
    }

    .card-body {
        padding: 2rem;
        background-color: var(--whatsapp-card-bg);
    }

    .info-item {
        font-size: 1.1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: flex-start; /* Align items to start for multi-line content */
    }

    .info-item i {
        font-size: 1.3rem;
        margin-right: 0.75rem;
        color: var(--whatsapp-text-muted); /* Muted color for icons */
        flex-shrink: 0; /* Prevent icon from shrinking */
        padding-top: 2px; /* Small adjustment for icon alignment */
    }

    .info-item strong {
        color: var(--whatsapp-text-dark);
        margin-right: 0.5rem;
        flex-shrink: 0; /* Prevent strong tag from shrinking */
    }

    .info-item .contact-text {
        word-break: break-all; /* Break long contact strings */
    }

    .text-whatsapp-dark-green {
        color: var(--whatsapp-green-dark); /* Specific dark green for headings */
    }

    .description-text {
        line-height: 1.8;
        font-size: 1.05rem;
        color: var(--whatsapp-text-dark);
    }

    .border-whatsapp-light {
        border-color: var(--whatsapp-border) !important; /* Use border variable */
    }

    .badge {
        font-size: 0.95rem;
        padding: 0.5em 0.9em;
        font-weight: 600;
    }

    .bg-whatsapp-offer {
        background-color: var(--whatsapp-offer-badge) !important;
    }
    .bg-whatsapp-service {
        background-color: var(--whatsapp-service-badge) !important;
    }

    .btn-whatsapp {
        background-color: var(--whatsapp-green-light); /* Lighter green for WhatsApp button */
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        font-size: 0.95rem;
        transition: background-color 0.2s ease, transform 0.1s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .btn-whatsapp:hover {
        background-color: var(--whatsapp-green-dark);
        color: white;
        transform: translateY(-1px);
    }

    .btn-whatsapp-chat { /* Style for chat button */
        background-color: var(--whatsapp-green-light);
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        font-size: 0.95rem;
        transition: background-color 0.2s ease, transform 0.1s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .btn-whatsapp-chat:hover {
        background-color: var(--whatsapp-green-dark);
        color: white;
        transform: translateY(-1px);
    }

    .btn-outline-whatsapp-secondary {
        color: var(--whatsapp-text-muted);
        border-color: var(--whatsapp-border);
        background-color: transparent;
        padding: 0.6rem 1.2rem;
        font-size: 0.95rem;
        transition: all 0.2s ease, transform 0.1s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    .btn-outline-whatsapp-secondary:hover {
        background-color: var(--whatsapp-light-hover);
        color: var(--whatsapp-text-dark);
        transform: translateY(-1px);
    }

    .btn-whatsapp-warning {
        background-color: var(--whatsapp-warning-button);
        border-color: var(--whatsapp-warning-button);
        color: white;
        font-weight: 600;
        transition: background-color 0.2s ease, transform 0.1s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .btn-whatsapp-warning:hover {
        background-color: #e0a800; /* Darker yellow */
        transform: translateY(-1px);
    }

    .btn-whatsapp-danger {
        background-color: var(--whatsapp-danger-button);
        border-color: var(--whatsapp-danger-button);
        color: white;
        font-weight: 600;
        transition: background-color 0.2s ease, transform 0.1s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .btn-whatsapp-danger:hover {
        background-color: #c82333; /* Darker red */
        transform: translateY(-1px);
    }

    .card-footer {
        background-color: var(--whatsapp-light-gray) !important;
        padding: 1.5rem 2rem;
        border-top: 1px solid var(--whatsapp-border);
    }

    .btn.rounded-pill {
        border-radius: 50rem !important;
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .container {
            padding: 0; /* Remove horizontal padding on mobile */
        }
        .listing-card {
            border-radius: 0; /* Full width on mobile */
            box-shadow: none !important;
        }
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
        .card-body {
            padding: 1.5rem;
        }
        .info-item {
            flex-direction: column; /* Stack info items vertically */
            align-items: flex-start; /* Align text to start */
        }
        .info-item strong.me-2 {
            margin-right: 0 !important;
            margin-bottom: 0.5rem; /* Add space below label */
        }
        .info-item .btn-whatsapp, .info-item .btn-whatsapp-chat {
            width: 100%; /* Full width buttons */
            margin-left: 0 !important;
            margin-top: 0.5rem;
        }
        .card-footer {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem 1.5rem;
        }
        .card-footer .btn, .card-footer form button {
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
