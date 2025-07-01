{{-- resources/views/admin/listings/edit.blade.php --}}
@extends('layouts.admin') {{-- Utilise le layout de ton panneau d'administration --}}

@section('title', 'Modifier l\'Annonce: ' . $listing->title . ' - Admin Jobela RDC')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="fas fa-edit me-2"></i> Modifier l'Annonce: "{{ $listing->title }}"
    </h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header">
            Formulaire de Modification d'Annonce
        </div>
        <div class="card-body">
            <form action="{{ route('admin.listings.update', $listing->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT') {{-- Indique à Laravel que c'est une requête PUT pour la mise à jour --}}

                <div class="mb-3">
                    <label for="title" class="form-label">Titre de l'annonce <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $listing->title) }}" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="5" required>{{ old('description', $listing->description) }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="category_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Sélectionnez une catégorie</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $listing->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Localisation <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location" name="location" value="{{ old('location', $listing->location) }}" required>
                </div>

                <div class="mb-3">
                    <label for="salary" class="form-label">Salaire / Prix (optionnel)</label>
                    <input type="text" class="form-control" id="salary" name="salary" value="{{ old('salary', $listing->salary) }}">
                    <div class="form-text">Ex: "Négociable", "200$ - 300$/mois", "50.000 FCFA"</div>
                </div>

                <div class="mb-3">
                    <label for="contact_email" class="form-label">Email de contact <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="{{ old('contact_email', $listing->contact_email) }}" required>
                </div>

                <div class="mb-3">
                    <label for="contact_phone" class="form-label">Téléphone de contact (optionnel)</label>
                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $listing->contact_phone) }}">
                </div>

                <div class="mb-3">
                    <label for="posted_by_type" class="form-label">Type de publication <span class="text-danger">*</span></label>
                    <select class="form-select" id="posted_by_type" name="posted_by_type" required>
                        <option value="job" {{ old('posted_by_type', $listing->posted_by_type) == 'job' ? 'selected' : '' }}>Offre d'emploi</option>
                        <option value="service" {{ old('posted_by_type', $listing->posted_by_type) == 'service' ? 'selected' : '' }}>Offre de service</option>
                        <option value="freelance" {{ old('posted_by_type', $listing->posted_by_type) == 'freelance' ? 'selected' : '' }}>Freelance</option>
                        {{-- Ajoute d'autres types si nécessaire --}}
                    </select>
                </div>

                <div class="mb-3">
                    <label for="user_id" class="form-label">Utilisateur Associé (si publié par un utilisateur existant)</label>
                    <select class="form-select" id="user_id" name="user_id">
                        <option value="">Sélectionnez un utilisateur (optionnel)</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $listing->user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Si non sélectionné, l'annonce sera considérée comme postée par l'administrateur ou un invité.</div>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="pending" {{ old('status', $listing->status) == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="approved" {{ old('status', $listing->status) == 'approved' ? 'selected' : '' }}>Approuvé</option>
                        <option value="rejected" {{ old('status', $listing->status) == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Mettre à Jour l'Annonce</button>
                <a href="{{ route('admin.listings.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>
@endsection
