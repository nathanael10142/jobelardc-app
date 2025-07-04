@extends('layouts.user')

@section('title', 'Modifier l\'Annonce - Jobela RDC')

@section('content')
<div class="content-section p-3">
    <h5 class="mb-3 whatsapp-heading">
        <i class="fas fa-edit me-2"></i> Modifier l'annonce
    </h5>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show whatsapp-alert" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card whatsapp-card shadow-sm p-4">
        {{-- Assurez-vous que l'action pointe vers la route 'update' avec l'ID de l'annonce --}}
        <form action="{{ route('listings.update', $listing->id) }}" method="POST" novalidate>
            @csrf
            @method('PUT') {{-- Important pour les requêtes de mise à jour --}}

            <div class="mb-3 form-group-whatsapp">
                <label for="title" class="form-label">Titre de l'annonce <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('title') is-invalid @enderror"
                       id="title"
                       name="title"
                       value="{{ old('title', $listing->title) }}" {{-- Pré-remplir avec la valeur existante --}}
                       required
                       placeholder="Ex: Développeur Laravel Junior">
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3 form-group-whatsapp">
                <label for="description" class="form-label">Description détaillée <span class="text-danger">*</span></label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description"
                              name="description"
                              rows="5"
                              required
                              placeholder="Décrivez ici les détails de l'annonce...">{{ old('description', $listing->description) }}</textarea> {{-- Pré-remplir --}}
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3 form-group-whatsapp">
                <label for="location" class="form-label">Lieu (Ville, Quartier, Province) <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('location') is-invalid @enderror"
                       id="location"
                       name="location"
                       value="{{ old('location', $listing->location) }}" {{-- Pré-remplir --}}
                       required
                       placeholder="Ex: Kinshasa, Gombe">
                @error('location')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3 form-group-whatsapp">
                <label for="salary" class="form-label">Salaire / Prix (optionnel)</label>
                <input type="text"
                       class="form-control @error('salary') is-invalid @enderror"
                       id="salary"
                       name="salary"
                       value="{{ old('salary', $listing->salary) }}" {{-- Pré-remplir --}}
                       placeholder="Ex: 500 USD/mois, Négociable, Sur devis">
                @error('salary')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3 form-group-whatsapp">
                <label for="contact_info" class="form-label">Information de Contact <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('contact_info') is-invalid @enderror"
                       id="contact_info"
                       name="contact_info"
                       value="{{ old('contact_info', $listing->contact_info) }}" {{-- Pré-remplir --}}
                       required
                       placeholder="Ex: +24399xxxxxxx ou mon.email@example.com">
                <small class="form-text text-whatsapp-muted">Ce contact sera visible par les personnes intéressées.</small>
                @error('contact_info')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4 form-check form-check-whatsapp">
                <input type="checkbox"
                       class="form-check-input @error('is_job_offer') is-invalid @enderror"
                       id="is_job_offer"
                       name="is_job_offer"
                       value="1"
                       {{ old('is_job_offer', $listing->is_job_offer) ? 'checked' : '' }}> {{-- Pré-remplir --}}
                <label class="form-check-label" for="is_job_offer">
                    Ceci est une offre d'emploi (décochez pour une offre de service)
                </label>
                @error('is_job_offer')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-whatsapp-primary rounded-pill px-4 py-2">
                <i class="fas fa-save me-2"></i> Enregistrer les modifications
            </button>
            <a href="{{ route('listings.show', $listing->id) }}" class="btn btn-whatsapp-secondary rounded-pill px-4 py-2 ms-2">
                <i class="fas fa-times-circle me-2"></i> Annuler
            </a>
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
        --whatsapp-secondary-button: #6c757d; /* Grey for secondary actions */
        --whatsapp-input-border: #D1D7DA; /* Border for form inputs */
        --whatsapp-input-focus-shadow: rgba(18, 140, 126, 0.25); /* Shadow for focused input */
    }

    body {
        background-color: var(--whatsapp-background);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--whatsapp-text-dark);
    }

    .content-section {
        max-width: 800px;
        margin: 20px auto;
        padding: 0;
    }

    .whatsapp-heading {
        color: var(--whatsapp-green-dark);
        font-weight: 700;
        display: flex;
        align-items: center;
        margin-bottom: 25px !important;
        font-size: 1.5rem;
    }

    .whatsapp-alert {
        background-color: #ffebee; /* Light red */
        color: #c62828; /* Darker red text */
        border-color: #ef9a9a; /* Red border */
        border-radius: 8px;
        padding: 1rem 1.5rem;
        font-size: 0.95rem;
    }
    .whatsapp-alert .btn-close {
        filter: none; /* Reset filter for close button */
        color: #c62828; /* Match text color */
    }
    .whatsapp-alert ul {
        list-style-type: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    .whatsapp-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        background-color: var(--whatsapp-card-bg);
    }

    /* Form groups and labels */
    .form-group-whatsapp .form-label {
        font-weight: 600;
        color: var(--whatsapp-text-dark);
        margin-bottom: 0.5rem;
    }

    .form-group-whatsapp .form-control {
        border-radius: 8px;
        border: 1px solid var(--whatsapp-input-border);
        padding: 0.75rem 1rem;
        font-size: 1rem;
        color: var(--whatsapp-text-dark);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-group-whatsapp .form-control:focus {
        border-color: var(--whatsapp-green-light);
        box-shadow: 0 0 0 0.25rem var(--whatsapp-input-focus-shadow);
        outline: none;
    }

    .form-group-whatsapp .invalid-feedback {
        font-size: 0.85rem;
        margin-top: 0.25rem;
    }

    /* Checkbox styling */
    .form-check-whatsapp .form-check-input {
        border-radius: 4px; /* Slightly rounded square */
        border: 1px solid var(--whatsapp-input-border);
        width: 1.25em;
        height: 1.25em;
        margin-top: 0.25em;
        transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-check-whatsapp .form-check-input:checked {
        background-color: var(--whatsapp-green-light);
        border-color: var(--whatsapp-green-light);
    }
    .form-check-whatsapp .form-check-input:focus {
        border-color: var(--whatsapp-green-light);
        box-shadow: 0 0 0 0.25rem var(--whatsapp-input-focus-shadow);
    }
    .form-check-whatsapp .form-check-label {
        font-weight: 600;
        color: var(--whatsapp-text-dark);
    }

    /* Buttons */
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

    .btn-whatsapp-secondary {
        background-color: var(--whatsapp-secondary-button);
        border-color: var(--whatsapp-secondary-button);
        color: white;
        font-weight: 600;
        transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.1s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .btn-whatsapp-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
        color: white;
        transform: translateY(-1px);
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .content-section {
            margin: 0;
            padding: 10px;
        }

        .whatsapp-heading {
            font-size: 1.2rem;
            margin-bottom: 15px !important;
        }

        .whatsapp-card {
            border-radius: 0;
            box-shadow: none;
        }

        .form-group-whatsapp .form-control {
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
        }

        .form-group-whatsapp .form-label {
            font-size: 0.9rem;
        }

        .form-check-whatsapp .form-check-label {
            font-size: 0.9rem;
        }

        .btn-whatsapp-primary, .btn-whatsapp-secondary {
            width: 100%;
            margin-top: 0.5rem; /* Espace entre les boutons sur mobile */
            padding: 0.8rem 1rem;
            font-size: 1rem;
        }
        .btn-whatsapp-primary.ms-2, .btn-whatsapp-secondary.ms-2 {
            margin-left: 0 !important; /* Supprime le marge gauche sur mobile */
        }
    }
</style>
@endpush
