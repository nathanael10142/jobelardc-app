{{-- resources/views/admin/listings/index.blade.php --}}
@extends('layouts.admin') {{-- Assure-toi que tu as un layout admin --}}

@section('title', 'Gestion des Annonces - Admin Jobela RDC')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="fas fa-clipboard-list me-2"></i> Gestion des Annonces
    </h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            Liste des Annonces
            <a href="{{ route('admin.listings.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle Annonce
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Posté par</th>
                            <th>Type</th>
                            <th>Lieu</th>
                            <th>Statut</th>
                            <th>Date Publication</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($listings as $listing)
                            <tr>
                                <td>{{ $listing->id }}</td>
                                <td>{{ $listing->title }}</td>
                                <td>{{ $listing->user->name ?? 'N/A' }}</td>
                                <td>{{ $listing->posted_by_type }}</td>
                                <td>{{ $listing->location }}</td>
                                <td>
                                    <form action="{{ route('admin.listings.updateStatus', $listing->id) }}" method="POST" class="d-inline-flex align-items-center">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="pending" {{ $listing->status == 'pending' ? 'selected' : '' }}>En attente</option>
                                            <option value="approved" {{ $listing->status == 'approved' ? 'selected' : '' }}>Approuvé</option>
                                            <option value="rejected" {{ $listing->status == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                                        </select>
                                    </form>
                                </td>
                                <td>{{ $listing->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.listings.show', $listing->id) }}" class="btn btn-info btn-sm me-1" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('admin.listings.edit', $listing->id) }}" class="btn btn-warning btn-sm me-1" title="Éditer"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('admin.listings.destroy', $listing->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?')" title="Supprimer"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Aucune annonce à gérer pour le moment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Pagination --}}
            <div class="d-flex justify-content-center">
                {{ $listings->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
