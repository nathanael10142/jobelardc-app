@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-whatsapp-green-dark mb-0">{{ __('Annonces d\'Emploi') }}</h2>
        @can('create job') {{-- Vérifie si l'utilisateur a la permission de créer un job --}}
            <a href="{{ route('jobs.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> {{ __('Publier une offre') }}
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show whatsapp-card mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($jobs->isEmpty())
        <div class="card whatsapp-card text-center py-5">
            <div class="card-body">
                <p class="card-text text-muted h4">{{ __('Aucune annonce d\'emploi n\'est disponible pour le moment.') }}</p>
                @can('create job')
                    <p class="card-text text-muted mt-3">{{ __('Soyez le premier à publier une offre !') }}</p>
                    <a href="{{ route('jobs.create') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-plus-circle me-2"></i> {{ __('Publier une offre') }}
                    </a>
                @endcan
            </div>
        </div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            @foreach ($jobs as $job)
                <div class="col">
                    <div class="card whatsapp-card h-100 d-flex flex-column">
                        <div class="card-header bg-whatsapp-green-light text-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 text-white">{{ $job->title }}</h5>
                            <span class="badge bg-light text-whatsapp-green-dark">{{ ucfirst($job->employment_type) }}</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <p class="card-text flex-grow-1">
                                {{ Str::limit($job->description, 120) }}
                            </p>
                            <ul class="list-group list-group-flush mt-3">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-building me-2 text-whatsapp-green-dark"></i>
                                    <strong>{{ __('Employeur:') }}</strong> {{ $job->employer->name ?? 'N/A' }}
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-tag me-2 text-whatsapp-green-dark"></i>
                                    <strong>{{ __('Catégorie:') }}</strong> {{ $job->category->name ?? 'N/A' }}
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-map-marker-alt me-2 text-whatsapp-green-dark"></i>
                                    <strong>{{ __('Lieu:') }}</strong> {{ $job->location ?? 'Non spécifié' }}
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-money-bill-wave me-2 text-whatsapp-green-dark"></i>
                                    <strong>{{ __('Salaire:') }}</strong> {{ $job->salary_range ?? 'Négociable' }}
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <small class="text-muted">{{ __('Publié il y a') }} {{ $job->created_at->diffForHumans() }}</small>
                            <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-primary">{{ __('Voir les détails') }}</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-5">
            {{ $jobs->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

@section('styles')
<style>
    .card-header.bg-whatsapp-green-light {
        background-color: var(--whatsapp-green-light) !important;
    }
    .card-title.text-white {
        color: var(--whatsapp-card-bg) !important;
    }
    .badge.bg-light {
        background-color: var(--whatsapp-card-bg) !important;
        color: var(--whatsapp-green-dark) !important;
        font-weight: 700;
    }
    .list-group-item {
        background-color: transparent !important;
        border-color: var(--whatsapp-border);
        font-size: 0.95rem;
        padding-left: 0;
    }
    .list-group-item:first-child {
        border-top-width: 0;
    }
    .list-group-item:last-child {
        border-bottom-width: 0;
    }
    .list-group-item i {
        font-size: 1rem;
        min-width: 20px;
    }
    .list-group-flush .list-group-item {
        border-left: 0;
        border-right: 0;
    }
    .card-footer {
        background-color: var(--whatsapp-hover-light);
        border-top: 1px solid var(--whatsapp-border);
    }
    .btn-sm.btn-primary {
        padding: 0.3rem 0.75rem;
        font-size: 0.875rem;
    }
    .h2 .fas {
        font-size: 1.5rem;
        margin-right: 10px;
    }
    .alert.whatsapp-card {
        background-color: var(--whatsapp-card-bg);
        color: var(--whatsapp-text-dark);
        border-radius: 10px;
        box-shadow: 0 2px 8px var(--whatsapp-shadow);
        border: 1px solid var(--whatsapp-green-light);
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
    }
    .alert.whatsapp-card .btn-close {
        font-size: 0.8rem;
        margin-left: auto;
    }
    .alert.alert-success {
        border-left: 5px solid var(--whatsapp-green-dark);
    }
</style>
@endsection
