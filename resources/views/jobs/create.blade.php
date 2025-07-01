@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card whatsapp-card">
        <div class="card-header">{{ __('Publier une Nouvelle Annonce d\'Emploi') }}</div>

        <div class="card-body">
            <form method="POST" action="{{ route('jobs.store') }}">
                @csrf

                {{-- Titre de l'emploi --}}
                <div class="row mb-3">
                    <label for="title" class="col-md-4 col-form-label text-md-end">{{ __('Titre de l\'emploi') }}</label>
                    <div class="col-md-6">
                        <input id="title" type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" required autocomplete="title" autofocus>
                        @error('title')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div class="row mb-3">
                    <label for="description" class="col-md-4 col-form-label text-md-end">{{ __('Description') }}</label>
                    <div class="col-md-6">
                        <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" rows="6" required>{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                {{-- Catégorie --}}
                <div class="row mb-3">
                    <label for="category_id" class="col-md-4 col-form-label text-md-end">{{ __('Catégorie') }}</label>
                    <div class="col-md-6">
                        <select id="category_id" class="form-select @error('category_id') is-invalid @enderror" name="category_id" required>
                            <option value="">{{ __('Sélectionnez une catégorie') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                {{-- Lieu --}}
                <div class="row mb-3">
                    <label for="location" class="col-md-4 col-form-label text-md-end">{{ __('Lieu (Ville, Province)') }}</label>
                    <div class="col-md-6">
                        <input id="location" type="text" class="form-control @error('location') is-invalid @enderror" name="location" value="{{ old('location') }}" autocomplete="location">
                        @error('location')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                {{-- Fourchette de Salaire --}}
                <div class="row mb-3">
                    <label for="salary_range" class="col-md-4 col-form-label text-md-end">{{ __('Fourchette de Salaire (Ex: 500$-700$)') }}</label>
                    <div class="col-md-6">
                        <input id="salary_range" type="text" class="form-control @error('salary_range') is-invalid @enderror" name="salary_range" value="{{ old('salary_range') }}" autocomplete="salary_range">
                        @error('salary_range')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                {{-- Type d'emploi --}}
                <div class="row mb-3">
                    <label for="employment_type" class="col-md-4 col-form-label text-md-end">{{ __('Type d\'emploi') }}</label>
                    <div class="col-md-6">
                        <select id="employment_type" class="form-select @error('employment_type') is-invalid @enderror" name="employment_type" required>
                            <option value="">{{ __('Sélectionnez un type') }}</option>
                            <option value="full-time" {{ old('employment_type') == 'full-time' ? 'selected' : '' }}>{{ __('Temps plein') }}</option>
                            <option value="part-time" {{ old('employment_type') == 'part-time' ? 'selected' : '' }}>{{ __('Temps partiel') }}</option>
                            <option value="contract" {{ old('employment_type') == 'contract' ? 'selected' : '' }}>{{ __('Contrat') }}</option>
                            <option value="temporary" {{ old('employment_type') == 'temporary' ? 'selected' : '' }}>{{ __('Temporaire') }}</option>
                            <option value="internship" {{ old('employment_type') == 'internship' ? 'selected' : '' }}>{{ __('Stage') }}</option>
                        </select>
                        @error('employment_type')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                {{-- Statut de l'annonce (souvent draft par défaut pour les employeurs) --}}
                <div class="row mb-3">
                    <label for="status" class="col-md-4 col-form-label text-md-end">{{ __('Statut') }}</label>
                    <div class="col-md-6">
                        <select id="status" class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>{{ __('Brouillon') }}</option>
                            <option value="published" {{ old('status') == 'published' || !old('status') ? 'selected' : '' }}>{{ __('Publié') }}</option> {{-- Publié par défaut si non spécifié --}}
                            <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>{{ __('Archivé') }}</option>
                        </select>
                        @error('status')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                {{-- Bouton de soumission --}}
                <div class="row mb-0">
                    <div class="col-md-6 offset-md-4">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Publier l\'annonce') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Pas de styles spécifiques ici car les formulaires sont déjà stylés dans layouts/app.blade.php */
    /* Les styles comme .form-control, .form-select, .btn-primary sont définis globalement. */
</style>
@endsection
