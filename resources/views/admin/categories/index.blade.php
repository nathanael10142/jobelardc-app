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
                    <h5 class="mb-0">Liste des catégories</h5>
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nouvelle catégorie
                    </a>
                </div>

                <div class="card-body">

                    {{-- FORMULAIRE DE RECHERCHE --}}
                    <form method="GET" action="{{ route('admin.categories.index') }}" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher une catégorie..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>

                    {{-- TABLEAU DES CATÉGORIES --}}
                    @if($categories->count())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nom</th>
                                        <th>Description</th>
                                        <th>Créé le</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categories as $category)
                                        <tr>
                                            <td>{{ $category->id }}</td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ Str::limit($category->description, 50) }}</td>
                                            <td>{{ $category->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-warning me-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger">
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
                            {{ $categories->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>

                    @else
                        <p class="text-center mt-4">Aucune catégorie trouvée.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

{{-- STYLE SPÉCIFIQUE POUR LA PAGINATION --}}
@push('styles')
<style>
    .pagination {
        font-size: 0.875rem;
    }

    .pagination .page-link {
        padding: 0.35rem 0.75rem;
        border-radius: 0.25rem;
    }

    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
        display: none !important; /* Masquer "Précédent" et "Suivant" */
    }
</style>
@endpush
@endsection
