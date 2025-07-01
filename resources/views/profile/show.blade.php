@extends('layouts.user')

@section('title', 'Mon Profil - Jobela RDC')

@section('content')
@php
    use Illuminate\Support\Str;

    $user = auth()->user();
    $avatar = $user->profile_picture ?? null;
    $isExternal = $avatar && Str::startsWith($avatar, ['http://', 'https://']);
@endphp

<div class="content-section p-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center bg-whatsapp-green text-white rounded-top">
            <div class="me-3">
                @if($avatar)
                    <img src="{{ $isExternal ? $avatar : asset('storage/' . $avatar) }}" alt="Photo de profil de {{ $user->name }}" class="avatar-thumbnail">
                @else
                    <img src="{{ asset('images/default-avatar.png') }}" alt="Avatar par défaut" class="avatar-thumbnail">
                @endif
            </div>
            <div>
                <h4 class="mb-0">{{ $user->name }}</h4>
                <small class="text-white-75">Membre depuis {{ $user->created_at->locale('fr')->translatedFormat('F Y') }}</small>
            </div>
        </div>

        <div class="card-body">
            <p>
                <i class="fas fa-envelope me-2 text-muted"></i>
                <strong>Email :</strong> {{ $user->email }}
            </p>

            @if($user->phone_number)
                <p>
                    <i class="fas fa-phone-alt me-2 text-muted"></i>
                    <strong>Téléphone :</strong> {{ $user->phone_number }}
                </p>
            @endif

            @if($user->location)
                <p>
                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                    <strong>Localisation :</strong> {{ $user->location }}
                </p>
            @endif

            @if($user->bio)
                <hr>
                <h6>À propos :</h6>
                <p>{{ $user->bio }}</p>
            @endif
        </div>

        <div class="card-footer text-end">
            <a href="{{ route('profile.edit') }}" class="btn btn-primary rounded-pill">
                <i class="fas fa-edit me-1"></i> Modifier mon profil
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-whatsapp-green {
        background-color: #008069 !important;
    }

    .avatar-thumbnail {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #128C7E;
        flex-shrink: 0;
    }

    .text-white-75 {
        color: rgba(255, 255, 255, 0.75);
    }

    .card-header {
        gap: 1rem;
        padding: 1rem 1.5rem;
    }

    .card-body h6 {
        font-weight: 600;
        margin-bottom: 0.75rem;
    }
</style>
@endpush
