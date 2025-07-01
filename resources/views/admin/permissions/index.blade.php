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
                    <h5 class="mb-0">Liste des permissions</h5>
                    <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nouvelle permission
                    </a>
                </div>

                <div class="card-body">

                    {{-- TABLEAU DES PERMISSIONS --}}
                    @if($permissions->count())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nom de la permission</th>
                                        <th>Créée le</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($permissions as $permission)
                                        <tr>
                                            <td>{{ $permission->id }}</td>
                                            <td>{{ $permission->name }}</td>
                                            <td>{{ $permission->created_at->format('d/m/Y') }}</td>
                                            <td class="text-center">
                                                {{-- Bouton Éditer --}}
                                                <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-warning btn-sm me-1" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                {{-- Bouton Supprimer --}}
                                                <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette permission ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Supprimer">
                                                        <i class="fas fa-trash-alt"></i>
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
                            {{ $permissions->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>

                    @else
                        <p class="text-center mt-4">Aucune permission trouvée.</p>
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
