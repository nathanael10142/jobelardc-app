@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
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
                    <a href="{{ route('admin.jobs.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('admin.jobs.*') ? 'active' : '' }}">
                        <i class="fas fa-fw fa-briefcase me-2"></i> {{ __('Offres d\'emploi') }}
                    </a>
                    <a href="{{ route('admin.listings.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('admin.listings.*') ? 'active' : '' }}">
                        <i class="fas fa-fw fa-scroll me-2"></i> {{ __('Annonces Générales') }}
                    </a>
                    {{-- Nouveau lien pour les messages --}}
                    <a href="{{ route('admin.messages.index') }}" class="list-group-item list-group-item-action {{ Request::routeIs('admin.messages.*') ? 'active' : '' }}">
                        <i class="fas fa-fw fa-comments me-2"></i> {{ __('Messages') }}
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

        <div class="col-md-9 col-lg-10">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card whatsapp-card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted">{{ __('Total Utilisateurs') }}</h5>
                            <p class="card-text h2 text-whatsapp-green-dark">{{ number_format($totalUsers) }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card whatsapp-card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted">{{ __('Offres d\'emploi Actives') }}</h5>
                            <p class="card-text h2 text-whatsapp-green-dark">{{ number_format($activeJobs) }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card whatsapp-card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted">{{ __('Employeurs Vérifiés') }}</h5>
                            <p class="card-text h2 text-whatsapp-green-dark">{{ number_format($verifiedEmployers) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card whatsapp-card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted">{{ __('Annonces en Attente') }}</h5>
                            <p class="card-text h2 text-whatsapp-green-dark">{{ number_format($pendingJobs) }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card whatsapp-card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted">{{ __('Total Catégories') }}</h5>
                            <p class="card-text h2 text-whatsapp-green-dark">{{ number_format($totalCategories) }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card whatsapp-card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted">{{ __('Total Annonces Générales') }}</h5>
                            <p class="card-text h2 text-whatsapp-green-dark">{{ number_format($totalJobListings) }}</p>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card whatsapp-card mb-4">
                <div class="card-header">{{ __('Activités Récentes') }}</div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Activité') }}</th>
                                <th>{{ __('Titre/Nom') }}</th>
                                <th>{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivities as $activity)
                                <tr>
                                    <td>{{ $activity['activity'] }}</td>
                                    <td>
                                        @if(isset($activity['link']))
                                            <a href="{{ $activity['link'] }}">{{ $activity['title'] }}</a>
                                        @else
                                            {{ $activity['title'] }}
                                        @endif
                                    </td>
                                    <td>{{ $activity['date'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">{{ __('Aucune activité récente à afficher.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card whatsapp-card">
                <div class="card-header">{{ __('Raccourcis') }}</div>
                <div class="card-body d-flex flex-wrap justify-content-around">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary m-2">
                        <i class="fas fa-user-cog me-2"></i> {{ __('Gérer les utilisateurs') }}
                    </a>
                   
                    <a href="{{ route('admin.listings.create') }}" class="btn btn-primary m-2">
                        <i class="fas fa-plus-circle me-2"></i> {{ __('Ajouter une annonce générale') }}
                    </a>
                    {{-- Nouveau raccourci pour gérer les messages --}}
                    <a href="{{ route('admin.messages.index') }}" class="btn btn-primary m-2">
                        <i class="fas fa-comments me-2"></i> {{ __('Gérer les messages') }}
                    </a>
                    <a href="#" class="btn btn-primary m-2">
                        <i class="fas fa-chart-line me-2"></i> {{ __('Voir les rapports') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
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

    @media (max-width: 767px) {
        .col-md-3, .col-md-9, .col-lg-2, .col-lg-10 {
            width: 100%;
            padding-left: var(--bs-gutter-x, 0.75rem);
            padding-right: var(--bs-gutter-x, 0.75rem);
        }
        .whatsapp-card {
            margin-bottom: 1rem;
        }
        .list-group-item {
            padding: 10px 15px;
        }
    }
</style>
@endsection
