{{-- resources/views/listings/create.blade.php --}}
@extends('layouts.user')

@section('title', 'Publier une Annonce - Jobela RDC')

@section('content')
<div class="content-section p-3">
    <h5 class="mb-3">Publier une nouvelle annonce</h5>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm p-4">
        <form action="{{ route('listings.store') }}" method="POST" novalidate>
            @csrf

            <div class="mb-3">
                <label for="title" class="form-label fw-semibold">Titre de l'annonce <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control @error('title') is-invalid @enderror" 
                       id="title" 
                       name="title" 
                       value="{{ old('title') }}" 
                       required 
                       placeholder="Ex: Développeur Laravel Junior">
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description détaillée <span class="text-danger">*</span></label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" 
                          name="description" 
                          rows="5" 
                          required
                          placeholder="Décrivez ici les détails de l'annonce...">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="location" class="form-label fw-semibold">Lieu (Ville, Quartier, Province) <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control @error('location') is-invalid @enderror" 
                       id="location" 
                       name="location" 
                       value="{{ old('location') }}" 
                       required 
                       placeholder="Ex: Kinshasa, Gombe">
                @error('location')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="salary" class="form-label fw-semibold">Salaire / Prix (optionnel)</label>
                <input type="text" 
                       class="form-control @error('salary') is-invalid @enderror" 
                       id="salary" 
                       name="salary" 
                       value="{{ old('salary') }}" 
                       placeholder="Ex: 500 USD/mois, Négociable, Sur devis">
                @error('salary')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="contact_info" class="form-label fw-semibold">Information de Contact <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control @error('contact_info') is-invalid @enderror" 
                       id="contact_info" 
                       name="contact_info" 
                       value="{{ old('contact_info') }}" 
                       required 
                       placeholder="Ex: +24399xxxxxxx ou mon.email@example.com">
                <small class="form-text text-muted">Ce contact sera visible par les personnes intéressées.</small>
                @error('contact_info')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" 
                       class="form-check-input @error('is_job_offer') is-invalid @enderror" 
                       id="is_job_offer" 
                       name="is_job_offer" 
                       value="1" 
                       {{ old('is_job_offer') ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="is_job_offer">
                    Ceci est une offre d'emploi (décochez pour une offre de service)
                </label>
                @error('is_job_offer')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-success rounded-pill px-4">
                <i class="fas fa-paper-plane me-2"></i> Publier l'annonce
            </button>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .form-label {
        color: #128C7E;
    }

    .btn-success {
        background-color: #128C7E;
        border-color: #128C7E;
        font-weight: 600;
    }

    .btn-success:hover, .btn-success:focus {
        background-color: #075e54;
        border-color: #075e54;
    }
</style>
@endpush
