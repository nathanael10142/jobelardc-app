@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">

    {{-- MESSAGES FLASH --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-10 offset-lg-1">

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des rôles</h5>
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nouveau rôle
                    </a>
                </div>

                <div class="card-body">

                    {{-- FORMULAIRE DE RECHERCHE (optionnel, si vous voulez rechercher des rôles) --}}
                    {{-- <form method="GET" action="{{ route('admin.roles.index') }}" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher un rôle..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form> --}}

                    {{-- TABLEAU DES RÔLES --}}
                    @if($roles->count())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nom du rôle</th>
                                        <th>Permissions associées</th>
                                        <th>Créé le</th>
                                        <th class="text-center">Actions</th> {{-- Centrer le titre des actions --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($roles as $role)
                                        <tr>
                                            <td>{{ $role->id }}</td>
                                            <td>{{ $role->name }}</td>
                                            <td>
                                                @forelse ($role->permissions as $permission)
                                                    <span class="badge bg-secondary">{{ $permission->name }}</span>
                                                @empty
                                                    <span class="text-muted">Aucune permission</span>
                                                @endforelse
                                            </td>
                                            <td>{{ $role->created_at->format('d/m/Y') }}</td>
                                            <td class="text-center"> {{-- Centrer les boutons d'action --}}
                                                {{-- Bouton Éditer --}}
                                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning btn-sm me-1" title="Modifier">
                                                    <i class="fas fa-edit"></i> {{-- Icône seule --}}
                                                </a>
                                                {{-- Bouton Supprimer --}}
                                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Supprimer">
                                                        <i class="fas fa-trash-alt"></i> {{-- Icône seule --}}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- PAGINATION --}}
                        <div class="d-flex justify-content-center mt-4">
                            {{ $roles->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>

                    @else
                        <p class="text-center mt-4">Aucun rôle trouvé.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

{{-- STYLE SPÉCIFIQUE POUR LA PAGINATION (si nécessaire) --}}
@push('styles')
<style>
    .pagination {
        font-size: 0.875rem;
    }

    .pagination .page-link {
        padding: 0.35rem 0.75rem;
        border-radius: 0.25rem;
    }

    /* Optionnel: Masquer "Précédent" et "Suivant" */
    /* .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
        display: none !important;
    } */
</style>
@endpush
@endsection
