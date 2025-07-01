@extends('layouts.user')

@section('title', 'Modifier mon profil - Jobela RDC')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-header bg-whatsapp-green text-white d-flex justify-content-between align-items-center rounded-top">
                    <h5 class="mb-0">Mise à jour du profil</h5>
                    <a href="{{ route('profile.show') }}" class="btn btn-sm btn-light text-dark rounded-pill">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                </div>

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        {{-- Avatar --}}
                        <div class="mb-4 text-center">
                            @php
                                $avatar = $user->profile_picture ?? null;
                                $isExternal = $avatar && Str::startsWith($avatar, ['http://', 'https://']);
                            @endphp

                            <img src="{{ $avatar ? ($isExternal ? $avatar : asset('storage/' . $avatar)) : asset('images/default-avatar.png') }}"
                                class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;" alt="Photo de profil">

                            <div class="mt-2">
                                <label for="profile_picture" class="form-label">Changer la photo</label>
                                <input type="file" name="profile_picture" id="profile_picture" class="form-control form-control-sm @error('profile_picture') is-invalid @enderror">
                                @error('profile_picture')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Nom --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Téléphone --}}
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Téléphone</label>
                            <input type="text" name="phone_number" value="{{ old('phone', $user->phone_number) }}"
                                class="form-control @error('phone_number') is-invalid @enderror">
                            @error('phone_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Localisation --}}
                        <div class="mb-3">
                            <label for="location" class="form-label">Localisation</label>
                            <input type="text" name="location" value="{{ old('location', $user->location) }}"
                                class="form-control @error('location') is-invalid @enderror">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Bio --}}
                        <div class="mb-3">
                            <label for="bio" class="form-label">Biographie / À propos</label>
                            <textarea name="bio" rows="3" class="form-control @error('bio') is-invalid @enderror">{{ old('bio', $user->bio) }}</textarea>
                            @error('bio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer text-end bg-light border-top-0">
                        <button type="submit" class="btn btn-success rounded-pill px-4">
                            <i class="fas fa-save me-1"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-whatsapp-green {
        background-color: #008069 !important;
    }

    .card-header h5 {
        font-weight: 600;
    }
</style>
@endpush
