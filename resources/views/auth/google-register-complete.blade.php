@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card whatsapp-card">
                <div class="card-header">{{ __('Compléter votre inscription Google à Jobela RDC') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register.google.complete.post') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Hidden fields for Google User Data --}}
                        <input type="hidden" name="name" value="{{ $googleUserData['name'] }}">
                        <input type="hidden" name="email" value="{{ $googleUserData['email'] }}">
                        <input type="hidden" name="google_id" value="{{ $googleUserData['google_id'] }}">
                        <input type="hidden" name="profile_picture_url" value="{{ $googleUserData['avatar'] }}">

                        {{-- Nom complet (Affichage seulement, de Google) --}}
                        <div class="row mb-3">
                            <label class="col-md-4 col-form-label text-md-end">{{ __('Nom complet (de Google)') }}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" value="{{ $googleUserData['name'] }}" disabled>
                            </div>
                        </div>

                        {{-- Adresse E-mail (Affichage seulement, de Google) --}}
                        <div class="row mb-3">
                            <label class="col-md-4 col-form-label text-md-end">{{ __('Adresse E-mail (de Google)') }}</label>
                            <div class="col-md-6">
                                <input type="email" class="form-control" value="{{ $googleUserData['email'] }}" disabled>
                            </div>
                        </div>

                        {{-- Numéro de Téléphone (Optionnel) --}}
                        <div class="row mb-3">
                            <label for="phone_number" class="col-md-4 col-form-label text-md-end">{{ __('Numéro de Téléphone') }}</label>
                            <div class="col-md-6">
                                <input id="phone_number" type="tel" class="form-control @error('phone_number') is-invalid @enderror" name="phone_number" value="{{ old('phone_number') }}">
                                @error('phone_number')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        {{-- Province --}}
                        <div class="row mb-3">
                            <label for="province" class="col-md-4 col-form-label text-md-end">{{ __('Province') }}</label>
                            <div class="col-md-6">
                                <select id="province" name="province" class="form-select @error('province') is-invalid @enderror" required>
                                    <option value="">Sélectionnez une province</option>
                                    @foreach ($provinces as $provinceName)
                                        <option value="{{ $provinceName }}" {{ old('province') == $provinceName ? 'selected' : '' }}>
                                            {{ $provinceName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('province')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        {{-- Ville --}}
                        <div class="row mb-3">
                            <label for="city" class="col-md-4 col-form-label text-md-end">{{ __('Ville') }}</label>
                            <div class="col-md-6">
                                <select id="city" name="city" class="form-select @error('city') is-invalid @enderror" required>
                                    <option value="">Sélectionnez une ville</option>
                                </select>
                                @error('city')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        {{-- Type d'utilisateur --}}
                        <div class="row mb-3">
                            <label for="user_type" class="col-md-4 col-form-label text-md-end">{{ __('Vous êtes :') }}</label>
                            <div class="col-md-6">
                                <select id="user_type" name="user_type" class="form-select @error('user_type') is-invalid @enderror" required>
                                    <option value="">Sélectionnez votre type</option>
                                    <option value="candidate" {{ old('user_type') == 'candidate' ? 'selected' : '' }}>Candidat (Je cherche un emploi)</option>
                                    <option value="employer" {{ old('user_type') == 'employer' ? 'selected' : '' }}>Employeur (Je propose des emplois)</option>
                                </select>
                                @error('user_type')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        {{-- Bio --}}
                        <div class="row mb-3">
                            <label for="bio" class="col-md-4 col-form-label text-md-end">{{ __('Bio') }}</label>
                            <div class="col-md-6">
                                <textarea id="bio" name="bio" class="form-control @error('bio') is-invalid @enderror" rows="3">{{ old('bio') }}</textarea>
                                @error('bio')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        {{-- Bouton --}}
                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Compléter l\'inscription') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const phoneNumberInput = document.getElementById('phone_number');
    const form = document.querySelector('form');

    const provincesAndCitiesData = @json(\App\Http\Controllers\Auth\RegisterController::getProvincesAndCitiesData());

    function populateCities(province) {
        citySelect.innerHTML = '<option value="">Sélectionnez une ville</option>';
        if (province && provincesAndCitiesData[province]) {
            provincesAndCitiesData[province].forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                if ("{{ old('city') }}" === city && province === "{{ old('province') }}") {
                    option.selected = true;
                }
                citySelect.appendChild(option);
            });
        }
    }

    if (provinceSelect) {
        provinceSelect.addEventListener('change', () => {
            populateCities(provinceSelect.value);
        });

        if (provinceSelect.value) {
            populateCities(provinceSelect.value);
        }
    }

    // Validation JS pour le numéro de téléphone
    form.addEventListener('submit', function (e) {
        phoneNumberInput.classList.remove('is-invalid');
        let existingError = phoneNumberInput.parentNode.querySelector('.invalid-feedback');
        if (existingError) existingError.remove();

        const phone = phoneNumberInput.value.trim();
        if (phone && !/^(?:\+243|0)[8-9]\d{8}$/.test(phone)) {
            e.preventDefault();
            phoneNumberInput.classList.add('is-invalid');
            const error = document.createElement('span');
            error.classList.add('invalid-feedback');
            error.setAttribute('role', 'alert');
            error.innerHTML = '<strong>Veuillez entrer un numéro de téléphone valide au format RDC (+243 ou 0, suivi de 8 ou 9, puis 8 chiffres).</strong>';
            phoneNumberInput.parentNode.appendChild(error);
        }
    });

    phoneNumberInput.addEventListener('input', function () {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
            let existingError = this.parentNode.querySelector('.invalid-feedback');
            if (existingError) existingError.remove();
        }
    });
});
</script>
@endsection
