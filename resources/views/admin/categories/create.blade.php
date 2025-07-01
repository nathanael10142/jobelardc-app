@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card whatsapp-card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Créer une nouvelle catégorie') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.categories.store') }}" method="POST">
                        @csrf

                        {{-- Nom --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Nom de la catégorie') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Description (optionnelle)') }}</label>
                            <textarea name="description" id="description" rows="3" 
                                class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Slug (readonly, optionnel) --}}
                        <div class="mb-3">
                            <label for="slug" class="form-label">{{ __('Slug (généré automatiquement)') }}</label>
                            <input type="text" id="slug" class="form-control" readonly>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary me-2">
                                {{ __('Annuler') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> {{ __('Enregistrer') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Script pour générer le slug à la volée --}}
@push('scripts')
<script>
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    nameInput.addEventListener('input', function() {
        let slug = this.value.toLowerCase()
            .replace(/ /g, '-')         // remplace les espaces par des tirets
            .replace(/[^\w-]+/g, '')   // supprime les caractères non alphanumériques sauf le tiret
            .replace(/--+/g, '-')      // remplace les doubles tirets par un seul
            .replace(/^-+|-+$/g, '');  // supprime les tirets au début et à la fin
        slugInput.value = slug;
    });
</script>
@endpush

@endsection
