{{-- resources/views/admin/listings/create.blade.php --}}
@extends('layouts.admin') {{-- Utilise le layout de ton panneau d'administration --}}

@section('title', 'Créer une Nouvelle Annonce - Admin Jobela RDC')

@section('content')
<div class="container-fluid py-4">
    {{-- Main Page Title (WhatsApp Green) --}}
    <h2 class="mb-4 text-whatsapp-green">
        <i class="fas fa-plus-circle me-2"></i> Créer une Nouvelle Annonce
    </h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Erreur de validation :</h5>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-lg border-0 rounded-3">
        {{-- Card Header (Solid WhatsApp Green) --}}
        <div class="card-header bg-whatsapp-green text-white py-3 rounded-top-3">
            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i> Formulaire de Création d'Annonce</h5>
        </div>
        <div class="card-body bg-light p-4">
            <form action="{{ route('admin.listings.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold text-dark">Titre de l'annonce <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" placeholder="Ex: Développeur Web Junior" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_id" class="form-label fw-bold text-dark">Catégorie <span class="text-danger">*</span></label>
                            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                <option value="">Sélectionnez une catégorie</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-bold text-dark">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="7" placeholder="Décrivez l'annonce en détail..." required>{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="location" class="form-label fw-bold text-dark">Localisation <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('location') is-invalid @enderror" id="location" name="location" value="{{ old('location') }}" placeholder="Ex: Kinshasa, Gombe" required>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="salary" class="form-label fw-bold text-dark">Salaire / Prix (optionnel)</label>
                            <input type="text" class="form-control @error('salary') is-invalid @enderror" id="salary" name="salary" value="{{ old('salary') }}" placeholder="Ex: Négociable, 200$ - 300$/mois">
                            <div class="form-text text-muted">Ex: "Négociable", "200$ - 300$/mois", "50.000 FCFA"</div>
                            @error('salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="contact_email" class="form-label fw-bold text-dark">Email de contact <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('contact_email') is-invalid @enderror" id="contact_email" name="contact_email" value="{{ old('contact_email') }}" placeholder="Ex: contact@entreprise.com" required>
                            @error('contact_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="contact_phone" class="form-label fw-bold text-dark">Téléphone de contact (optionnel)</label>
                            <input type="text" class="form-control @error('contact_phone') is-invalid @enderror" id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" placeholder="Ex: +243 999 123 456">
                            @error('contact_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="posted_by_type" class="form-label fw-bold text-dark">Type de publication <span class="text-danger">*</span></label>
                            <select class="form-select @error('posted_by_type') is-invalid @enderror" id="posted_by_type" name="posted_by_type" required>
                                <option value="">Sélectionnez le type</option>
                                <option value="job" {{ old('posted_by_type') == 'job' ? 'selected' : '' }}>Offre d'emploi</option>
                                <option value="service" {{ old('posted_by_type') == 'service' ? 'selected' : '' }}>Offre de service</option>
                                <option value="freelance" {{ old('posted_by_type') == 'freelance' ? 'selected' : '' }}>Freelance</option>
                                {{-- Ajoute d'autres types si nécessaire --}}
                            </select>
                            @error('posted_by_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status" class="form-label fw-bold text-dark">Statut <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approuvé</option>
                                <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="user_id" class="form-label fw-bold text-dark">Utilisateur Associé (si publié par un utilisateur existant)</label>
                    <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
                        <option value="">Sélectionnez un utilisateur (optionnel)</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text text-muted">Si non sélectionné, l'annonce sera considérée comme postée par l'administrateur ou un invité.</div>
                    @error('user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i> Créer l'Annonce</button>
                    <a href="{{ route('admin.listings.index') }}" class="btn btn-secondary btn-lg"><i class="fas fa-times-circle me-2"></i> Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Custom styles for WhatsApp green --}}
@push('styles')
<style>
    .text-whatsapp-green {
        color: #128C7E; /* A darker, professional WhatsApp green */
    }
    .bg-whatsapp-green {
        background-color: #25D366 !important; /* WhatsApp primary green */
    }
</style>
@endpush
@endsection
