{{-- resources/views/listings/edit.blade.php --}}
@extends('layouts.user') {{-- Assuming your main layout for user-facing pages --}}

@section('title', 'Modifier l\'Annonce : ' . $listing->title . ' - Jobela RDC')

@section('content')
<div class="container py-4">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6>Veuillez corriger les erreurs suivantes :</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden listing-card">
        <div class="card-header bg-whatsapp-green text-white py-3 px-4 d-flex align-items-center">
            <h4 class="mb-0 flex-grow-1"><i class="fas fa-edit me-2"></i> Modifier l'Annonce : {{ $listing->title }}</h4>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('listings.update', $listing) }}" method="POST">
                @csrf
                @method('PUT') {{-- Use PUT method for update operations --}}

                <div class="mb-3">
                    <label for="title" class="form-label fw-bold text-whatsapp-primary">Titre de l'annonce <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-whatsapp @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $listing->title) }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-bold text-whatsapp-primary">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control form-control-whatsapp @error('description') is-invalid @enderror" id="description" name="description" rows="5" required>{{ old('description', $listing->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label fw-bold text-whatsapp-primary">Localisation <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-whatsapp @error('location') is-invalid @enderror" id="location" name="location" value="{{ old('location', $listing->location) }}" required>
                    @error('location')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="salary" class="form-label fw-bold text-whatsapp-primary">Salaire / Prix (optionnel)</label>
                    <input type="text" class="form-control form-control-whatsapp @error('salary') is-invalid @enderror" id="salary" name="salary" value="{{ old('salary', $listing->salary) }}">
                    @error('salary')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="contact_info" class="form-label fw-bold text-whatsapp-primary">Informations de contact (optionnel)</label>
                    <input type="text" class="form-control form-control-whatsapp @error('contact_info') is-invalid @enderror" id="contact_info" name="contact_info" value="{{ old('contact_info', $listing->contact_info) }}">
                    <div class="form-text text-muted">Ex: Numéro de téléphone, adresse e-mail, etc.</div>
                    @error('contact_info')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-whatsapp-primary">Type d'annonce <span class="text-danger">*</span></label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input @error('is_job_offer') is-invalid @enderror" type="radio" name="is_job_offer" id="job_offer" value="1" {{ old('is_job_offer', $listing->is_job_offer) ? 'checked' : '' }} required>
                        <label class="form-check-label" for="job_offer">Offre d'emploi</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input @error('is_job_offer') is-invalid @enderror" type="radio" name="is_job_offer" id="service_offer" value="0" {{ old('is_job_offer', $listing->is_job_offer) ? '' : 'checked' }} required>
                        <label class="form-check-label" for="service_offer">Offre de service</label>
                    </div>
                    @error('is_job_offer')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="{{ route('listings.show', $listing) }}" class="btn btn-outline-secondary-jobela rounded-pill d-flex align-items-center">
                        <i class="fas fa-times me-2"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-whatsapp rounded-pill d-flex align-items-center">
                        <i class="fas fa-save me-2"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
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
        --jobela-primary: #0d6efd;
        --jobela-secondary: #6c757d;
        --jobela-offer-badge: #0d6efd;
        --jobela-service-badge: #6c757d;
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
        margin-bottom: 0;
        color: white; /* Ensure header text is white */
    }

    .text-whatsapp-primary {
        color: var(--whatsapp-dark-green); /* For labels */
    }

    /* Custom style for form controls to match WhatsApp design */
    .form-control-whatsapp {
        border-radius: 0.5rem; /* Slightly rounded corners */
        border: 1px solid var(--whatsapp-light-gray);
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control-whatsapp:focus {
        border-color: var(--whatsapp-light-green);
        box-shadow: 0 0 0 0.25rem rgba(18, 140, 126, 0.25); /* Subtle green glow */
    }

    .form-control-whatsapp.is-invalid {
        border-color: #dc3545; /* Bootstrap red for invalid fields */
    }

    .btn-whatsapp {
        background-color: var(--whatsapp-light-green);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        font-size: 1.1rem;
        transition: background-color 0.2s ease;
    }

    .btn-whatsapp:hover {
        background-color: var(--whatsapp-dark-green);
        color: white;
    }

    .btn-outline-secondary-jobela {
        color: var(--jobela-secondary);
        border-color: var(--jobela-secondary);
        padding: 0.75rem 1.5rem;
        font-size: 1.1rem;
        transition: all 0.2s ease;
    }
    .btn-outline-secondary-jobela:hover {
        background-color: var(--jobela-secondary);
        color: white;
    }

    .btn.rounded-pill {
        border-radius: 50rem !important;
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .card-header {
            padding: 1rem 1.5rem;
        }
        .card-header h4 {
            font-size: 1.5rem;
        }
        .card-body {
            padding: 1.5rem;
        }
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
        }
        .d-flex.justify-content-between .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush
