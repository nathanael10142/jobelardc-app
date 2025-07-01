{{-- resources/views/admin/users/index.blade.php --}}

@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        {{-- Barre latérale de navigation de l'administrateur --}}
        <div class="col-md-3 col-lg-2 mb-4">
            <div class="card whatsapp-card h-100">
                <div class="card-header">{{ __('Navigation Admin') }}</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('admin.dashboard') }}" class="list-group-item list-group-item-action {{ Request::routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-fw fa-tachometer-alt me-2"></i> {{ __('Tableau de bord') }}
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="fas fa-fw fa-users me-2"></i> {{ __('Utilisateurs') }}
                    </a>

                    <a href="{{ route('admin.categories.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('admin.categories.*') ? 'active' : '' }}">
                        <i class="fas fa-fw fa-th-list me-2"></i> {{ __('Catégories') }}
                    </a>
                    <a href="{{ route('admin.roles.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('admin.roles.*') ? 'active' : '' }}">
                        <i class="fas fa-fw fa-user-tag me-2"></i> {{ __('Rôles') }}
                    </a>
                    <a href="{{ route('admin.permissions.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('admin.permissions.*') ? 'active' : '' }}">
                        <i class="fas fa-fw fa-lock me-2"></i> {{ __('Permissions') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Zone de contenu principale pour les utilisateurs --}}
        <div class="col-md-9 col-lg-10">
            <div class="card whatsapp-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    {{ __('Gestion des Utilisateurs') }}
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">{{ __('Ajouter un utilisateur') }}</a>
                </div>
                <div class="card-body">
                    {{-- Zone de recherche ajoutée ici (FORMULAIRE CLASSIQUE) --}}
                    <div class="row mb-3">
                        <div class="col-md-6 offset-md-3">
                            {{-- Le formulaire soumettra la recherche via GET --}}
                            <form action="{{ route('admin.users.index') }}" method="GET" class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="{{ __('Rechercher par nom ou email...') }}" value="{{ request('search') }}">
                                <button type="submit" class="btn btn-primary">{{ __('Rechercher') }}</button>
                                {{-- Bouton pour effacer la recherche si un terme est déjà présent --}}
                                @if(request('search'))
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-danger">{{ __('Effacer') }}</a>
                                @endif
                            </form>
                        </div>
                    </div>
                    {{-- Fin de la zone de recherche --}}

                    {{-- Tableau des utilisateurs (plus de div #users-table-container car plus d'AJAX) --}}
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Nom') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Vérifié') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ ucfirst($user->user_type) }}</td>
                                        <td>
                                            @if ($user->hasVerifiedEmail())
                                                <i class="fas fa-check-circle text-success me-1"></i> {{ __('Oui') }}
                                            @else
                                                <i class="fas fa-times-circle text-danger me-1"></i> {{ __('Non') }}
                                            @endif
                                        </td>
                                        <td>
                                            {{-- Liens d'actions --}}
                                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-info me-1" title="{{ __('Voir') }}"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-warning me-1" title="{{ __('Modifier') }}"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Supprimer') }}" onclick="return confirm('{{ __('Êtes-vous sûr de vouloir supprimer cet utilisateur ?') }}')"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">{{ __('Aucun utilisateur trouvé.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-center">
                        {{ $users->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Styles de la barre latérale (copiés du dashboard admin pour consistance) */
    .list-group-item.active {
        background-color: var(--whatsapp-green-light) !important;
        border-color: var(--whatsapp-green-light) !important;
        color: white !important;
    }
    .list-group-item.active i {
        color: white !important;
    }
    .list-group-item:hover {
        background-color: var(--whatsapp-hover-light);
        color: var(--whatsapp-text-dark);
    }
    .list-group-item {
        border-color: var(--whatsapp-border);
        color: var(--whatsapp-text-dark);
        font-weight: 600;
        transition: background-color 0.2s, color 0.2s;
        display: flex;
        align-items: center;
        padding: 12px 20px;
    }
    .list-group-item i {
        font-size: 1.1rem;
        margin-right: 10px;
        color: var(--whatsapp-green-dark);
    }
    .text-whatsapp-green-dark {
        color: var(--whatsapp-green-dark);
    }
    .card-title.text-muted {
        color: var(--whatsapp-text-muted) !important;
    }

    /* Styles spécifiques pour le tableau des utilisateurs */
    .table thead th {
        background-color: var(--whatsapp-bg-light); /* En-tête de tableau plus clair */
        color: var(--whatsapp-text-dark);
        border-bottom: 2px solid var(--whatsapp-border);
    }
    .table-hover tbody tr:hover {
        background-color: var(--whatsapp-hover-light);
    }
    .btn-sm {
        padding: .25rem .5rem;
        font-size: .875rem;
        border-radius: .2rem;
    }
    .btn-info {
        background-color: #17a2b8; /* Bleu info Bootstrap */
        border-color: #17a2b8;
        color: white;
    }
    .btn-warning {
        background-color: #ffc107; /* Jaune warning Bootstrap */
        border-color: #ffc107;
        color: black;
    }
    .btn-danger {
        background-color: #dc3545; /* Rouge danger Bootstrap */
        border-color: #dc3545;
        color: white;
    }

    /* Ajustements pour les écrans plus petits */
    @media (max-width: 767px) {
        .col-md-3, .col-md-9, .col-lg-2, .col-lg-10 {
            padding-left: var(--bs-gutter-x, 0.75rem);
            padding-right: var(--bs-gutter-x, 0.75rem);
        }
        .whatsapp-card {
            margin-bottom: 1rem;
        }
        .list-group-item {
            padding: 10px 15px;
        }
        .table-responsive {
            overflow-x: auto; /* Permet le défilement horizontal sur les petits écrans */
        }
    }
</style>
@endsection

@section('scripts')
{{-- Aucun script JavaScript n'est nécessaire ici pour la recherche ou la pagination si c'est une approche standard --}}
@endsection
