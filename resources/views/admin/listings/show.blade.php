{{-- resources/views/admin/listings/show.blade.php --}}
@extends('layouts.admin') {{-- Utilise le layout de ton panneau d'administration --}}

@section('title', 'Détails de l\'Annonce: ' . $listing->title . ' - Admin Jobela RDC')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="fas fa-info-circle me-2"></i> Détails de l'Annonce
    </h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            Annonce: <strong>{{ $listing->title }}</strong>
            <div>
                <a href="{{ route('admin.listings.edit', $listing->id) }}" class="btn btn-warning btn-sm me-2" title="Éditer cette annonce">
                    <i class="fas fa-edit me-1"></i> Éditer
                </a>
                <form action="{{ route('admin.listings.destroy', $listing->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.')" title="Supprimer cette annonce">
                        <i class="fas fa-trash me-1"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>ID de l'annonce:</strong> {{ $listing->id }}</p>
                    <p><strong>Titre:</strong> {{ $listing->title }}</p>
                    <p><strong>Description:</strong></p>
                    <div class="card card-body bg-light mb-3">
                        {!! nl2br(e($listing->description)) !!} {{-- nl2br pour les sauts de ligne, e() pour la sécurité --}}
                    </div>
                    <p><strong>Catégorie:</strong> {{ $listing->category->name ?? 'N/A' }}</p>
                    <p><strong>Localisation:</strong> {{ $listing->location }}</p>
                    <p><strong>Salaire/Prix:</strong> {{ $listing->salary ?? 'Non spécifié' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Email de contact:</strong> {{ $listing->contact_email }}</p>
                    <p><strong>Téléphone de contact:</strong> {{ $listing->contact_phone ?? 'Non spécifié' }}</p>
                    <p><strong>Type de publication:</strong> {{ ucfirst($listing->posted_by_type) }}</p>
                    <p><strong>Posté par (Utilisateur):</strong>
                        @if($listing->user)
                            <a href="{{ route('admin.users.show', $listing->user->id) }}">{{ $listing->user->name }} ({{ $listing->user->email }})</a>
                        @else
                            Anonyme ou Utilisateur supprimé
                        @endif
                    </p>
                    <p><strong>Date de publication:</strong> {{ $listing->created_at->format('d/m/Y H:i:s') }}</p>
                    <p><strong>Dernière mise à jour:</strong> {{ $listing->updated_at->format('d/m/Y H:i:s') }}</p>
                    <p>
                        <strong>Statut:</strong>
                        <span class="badge {{ $listing->status == 'approved' ? 'bg-success' : ($listing->status == 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ ucfirst($listing->status) }}
                        </span>
                    </p>
                </div>
            </div>
            <hr>
            <div class="mb-3">
                <form action="{{ route('admin.listings.updateStatus', $listing->id) }}" method="POST" class="d-inline-flex align-items-center">
                    @csrf
                    @method('PUT')
                    <label for="status_update" class="form-label me-2 mb-0">Modifier le statut:</label>
                    <select name="status" id="status_update" class="form-select form-select-sm me-2" style="width: auto;">
                        <option value="pending" {{ $listing->status == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="approved" {{ $listing->status == 'approved' ? 'selected' : '' }}>Approuvé</option>
                        <option value="rejected" {{ $listing->status == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Appliquer</button>
                </form>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <a href="{{ route('admin.listings.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour à la liste des annonces
        </a>
    </div>
</div>
@endsection
