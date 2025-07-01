@extends('layouts.admin') {{-- Assurez-vous que c'est le bon layout d'administration --}}

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        {{-- Sidebar de navigation (peut être incluse via un @include si elle est répétée) --}}
        <div class="col-md-3 col-lg-2 mb-4">
            <div class="card whatsapp-card h-100">
                <div class="card-header">{{ __('Navigation Admin') }}</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('admin.dashboard') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-fw fa-tachometer-alt me-2"></i> {{ __('Tableau de bord') }}
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action active">
                        <i class="fas fa-fw fa-users me-2"></i> {{ __('Utilisateurs') }}
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-fw fa-th-list me-2"></i> {{ __('Catégories') }}
                    </a>
                    <a href="{{ route('admin.roles.index') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-fw fa-user-tag me-2"></i> {{ __('Rôles') }}
                    </a>
                    <a href="{{ route('admin.permissions.index') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-fw fa-lock me-2"></i> {{ __('Permissions') }}
                    </a>
                    {{-- Ajoutez d'autres liens de navigation admin si nécessaire --}}
                </div>
            </div>
        </div>

        {{-- Contenu principal pour la création d'utilisateur --}}
        <div class="col-md-9 col-lg-10">
            <div class="card whatsapp-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    {{ __('Créer un nouvel utilisateur') }}
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm whatsapp-btn-secondary"> {{-- Added whatsapp-btn-secondary --}}
                        <i class="fas fa-arrow-left me-1"></i> {{ __('Retour à la liste') }}
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Nom') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('Adresse E-mail') }} <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Mot de passe') }} <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('Confirmer le mot de passe') }} <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>

                        {{-- Champ user_type --}}
                        <div class="mb-3">
                            <label for="user_type" class="form-label">{{ __('Type d\'utilisateur') }} <span class="text-danger">*</span></label>
                            <select class="form-select @error('user_type') is-invalid @enderror" id="user_type" name="user_type" required>
                                <option value="">{{ __('Sélectionner un type') }}</option>
                                <option value="candidate" {{ old('user_type') == 'candidate' ? 'selected' : '' }}>{{ __('Candidat') }}</option>
                                <option value="employer" {{ old('user_type') == 'employer' ? 'selected' : '' }}>{{ __('Employeur') }}</option>
                                <option value="admin" {{ old('user_type') == 'admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
                                <option value="super_admin" {{ old('user_type') == 'super_admin' ? 'selected' : '' }}>{{ __('Super Admin') }}</option>
                            </select>
                            @error('user_type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Assignation des rôles (Spatie) --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('Rôles') }}</label>
                            <div class="row">
                                @foreach($roles as $role)
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input @error('roles') is-invalid @enderror" type="checkbox" name="roles[]" value="{{ $role->name }}" id="role_{{ $role->id }}"
                                                {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="role_{{ $role->id }}">
                                                {{ ucfirst($role->name) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('roles')
                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                            @error('roles.*') {{-- Pour les erreurs sur chaque rôle --}}
                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-success whatsapp-btn-primary">{{ __('Créer l\'utilisateur') }}</button> {{-- Added whatsapp-btn-primary --}}
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* WhatsApp-like Color Palette */
    :root {
        --whatsapp-primary: #075E54;       /* Dark Green (Header/Accent) */
        --whatsapp-secondary: #128C7E;     /* Lighter Green (Button) */
        --whatsapp-bg: #ECE5DD;            /* Light Background (Chat Background) */
        --whatsapp-header-bg: #008069;     /* Header background (Darker green for card headers) */
        --whatsapp-bubble-received: #FFFFFF; /* White/Light for received messages/cards */
        --whatsapp-bubble-sent: #DCF8C6;   /* Light Green for sent messages */
        --whatsapp-text-dark: #333333;     /* Dark text */
        --whatsapp-text-muted: #666666;    /* Muted text */
        --whatsapp-border: #DDDDDD;       /* Light border */
        --whatsapp-input-bg: #F0F2F5;     /* Input field background */
        --whatsapp-input-border: #CCCCCC; /* Input border */
        --whatsapp-green-light: #25D366;   /* Main WhatsApp green */
        --whatsapp-green-dark: #075E54;    /* Darker WhatsApp green */
        --whatsapp-hover-light: #F5F5F5;   /* Light hover effect */
        --whatsapp-red-error: #DC3545;     /* Red for errors */
    }

    body {
        background-color: var(--whatsapp-bg); /* Apply main background to body */
        color: var(--whatsapp-text-dark);
    }

    /* Styles de la barre latérale */
    .list-group-item.active {
        background-color: var(--whatsapp-primary) !important; /* Dark green for active item */
        border-color: var(--whatsapp-primary) !important;
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

    /* Styles généraux des cartes et formulaires */
    .whatsapp-card {
        background-color: var(--whatsapp-bubble-received); /* White/light background for cards */
        border: 1px solid var(--whatsapp-border);
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .card-header {
        background-color: var(--whatsapp-header-bg); /* Darker green for card headers */
        color: white; /* Header text color */
        border-bottom: 1px solid var(--whatsapp-border);
        padding: 1rem 1.25rem;
    }
    .form-label {
        font-weight: 600;
        color: var(--whatsapp-text-dark);
    }
    .form-control, .form-select {
        background-color: var(--whatsapp-input-bg);
        border: 1px solid var(--whatsapp-input-border);
        color: var(--whatsapp-text-dark);
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--whatsapp-secondary); /* Lighter green on focus */
        box-shadow: 0 0 0 0.25rem rgba(18, 140, 126, 0.25); /* whatsapp-secondary with opacity */
    }
    .is-invalid {
        border-color: var(--whatsapp-red-error);
    }
    .invalid-feedback, .text-danger {
        color: var(--whatsapp-red-error) !important;
    }
    .form-check-input:checked {
        background-color: var(--whatsapp-secondary); /* Lighter green for checked checkboxes */
        border-color: var(--whatsapp-secondary);
    }
    .form-check-label {
        color: var(--whatsapp-text-dark);
    }

    /* Buttons specific styles */
    .whatsapp-btn-primary {
        background-color: var(--whatsapp-secondary); /* Lighter green for primary actions */
        border-color: var(--whatsapp-secondary);
        color: white;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    .whatsapp-btn-primary:hover {
        background-color: #075E54; /* Darker on hover */
        border-color: #075E54;
        color: white;
    }

    .whatsapp-btn-secondary {
        background-color: var(--whatsapp-border); /* Muted background for secondary actions */
        border-color: var(--whatsapp-border);
        color: var(--whatsapp-text-dark);
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    .whatsapp-btn-secondary:hover {
        background-color: #C0C0C0; /* Slightly darker on hover */
        border-color: #C0C0C0;
        color: var(--whatsapp-text-dark);
    }

    /* Small screen adjustments */
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
    }
</style>
@endsection

@section('scripts')
{{-- Aucun script JavaScript supplémentaire nécessaire pour ce formulaire simple --}}
@endsection
