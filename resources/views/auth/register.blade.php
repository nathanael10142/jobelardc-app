@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card whatsapp-card"> {{-- Utilise la classe personnalisée de la carte WhatsApp --}}
                <div class="card-header">{{ __('Inscription à Jobela RDC') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Nom complet --}}
                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('Nom complet') }}</label>
                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Adresse E-mail --}}
                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Adresse E-mail') }}</label>
                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Numéro de Téléphone (Optionnel) --}}
                        <div class="row mb-3">
                            <label for="phone_number" class="col-md-4 col-form-label text-md-end">{{ __('Numéro de Téléphone (Optionnel)') }}</label>
                            <div class="col-md-6">
                                {{-- La classe form-control est stylée dans le layout pour l'arrondi et le padding --}}
                                <input id="phone_number" type="tel" class="form-control @error('phone_number') is-invalid @enderror" name="phone_number" value="{{ old('phone_number') }}" autocomplete="phone_number">
                                @error('phone_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Province --}}
                        <div class="row mb-3">
                            <label for="province" class="col-md-4 col-form-label text-md-end">{{ __('Province') }}</label>
                            <div class="col-md-6">
                                {{-- La classe form-select est stylée dans le layout --}}
                                <select id="province" class="form-select @error('province') is-invalid @enderror" name="province" required>
                                    <option value="">Sélectionnez une province</option>
                                    {{-- Les noms des provinces sont injectés par le contrôleur (array_keys de $provincesVilles) --}}
                                    @foreach ($provinces as $provinceName)
                                        <option value="{{ $provinceName }}" {{ old('province') == $provinceName ? 'selected' : '' }}>
                                            {{ $provinceName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('province')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Ville --}}
                        <div class="row mb-3">
                            <label for="city" class="col-md-4 col-form-label text-md-end">{{ __('Ville') }}</label>
                            <div class="col-md-6">
                                {{-- La classe form-select est stylée dans le layout --}}
                                <select id="city" class="form-select @error('city') is-invalid @enderror" name="city" required>
                                    <option value="">Sélectionnez une ville</option>
                                    {{-- Les options de ville seront chargées dynamiquement via AJAX --}}
                                </select>
                                @error('city')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Type d'utilisateur --}}
                        <div class="row mb-3">
                            <label for="user_type" class="col-md-4 col-form-label text-md-end">{{ __('Vous êtes :') }}</label>
                            <div class="col-md-6">
                                {{-- La classe form-select est stylée dans le layout --}}
                                <select id="user_type" class="form-select @error('user_type') is-invalid @enderror" name="user_type" required>
                                    <option value="">Sélectionnez votre type</option>
                                    <option value="candidate" {{ old('user_type') == 'candidate' ? 'selected' : '' }}>Candidat (Je cherche un emploi)</option>
                                    <option value="employer" {{ old('user_type') == 'employer' ? 'selected' : '' }}>Employeur (Je propose des emplois)</option>
                                </select>
                                @error('user_type')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Bio (Décrivez-vous brièvement) --}}
                        <div class="row mb-3">
                            <label for="bio" class="col-md-4 col-form-label text-md-end">{{ __('Bio (Décrivez-vous brièvement)') }}</label>
                            <div class="col-md-6">
                                {{-- La classe form-control (ou form-textarea) est stylée dans le layout --}}
                                <textarea id="bio" class="form-control @error('bio') is-invalid @enderror" name="bio" rows="3">{{ old('bio') }}</textarea>
                                @error('bio')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Photo de profil (Optionnel) --}}
                        <div class="row mb-3">
                            <label for="profile_picture" class="col-md-4 col-form-label text-md-end">{{ __('Photo de profil (Optionnel)') }}</label>
                            <div class="col-md-6">
                                {{-- La classe form-control est stylée dans le layout --}}
                                <input id="profile_picture" type="file" class="form-control @error('profile_picture') is-invalid @enderror" name="profile_picture">
                                @error('profile_picture')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Mot de passe --}}
                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Mot de passe') }}</label>
                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Confirmer le mot de passe --}}
                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirmer le mot de passe') }}</label>
                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        {{-- Bouton d'inscription --}}
                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary"> {{-- Utilise la classe btn-primary stylée dans le layout --}}
                                    {{ __('S\'inscrire') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- Lien vers la page de connexion --}}
                    <div class="row mt-3">
                        <div class="col-md-8 offset-md-4">
                            <p class="mb-0">
                                {{ __('Déjà un compte ?') }} <a href="{{ route('login') }}" class="btn-link">{{ __('Se connecter ici') }}</a> {{-- Utilise la classe btn-link stylée dans le layout --}}
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM content loaded for register.blade.php (AJAX version)');

        const provinceSelect = document.getElementById('province');
        const citySelect = document.getElementById('city');
        const phoneNumberInput = document.getElementById('phone_number');
        const registerForm = document.querySelector('form');

        // Fonction asynchrone pour charger les villes via AJAX
        async function loadCities(selectedProvince) {
            console.log('Attempting to load cities for:', selectedProvince);
            citySelect.innerHTML = '<option value="">Chargement...</option>'; // Message de chargement

            try {
                // Utilisez la route nommée de Laravel pour une URL robuste
                // C'est la modification clé pour résoudre l'erreur de "Mixed Content"
                const url = "{{ route('get.cities.by.province') }}?province=" + encodeURIComponent(selectedProvince);

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest', // Indique que c'est une requête AJAX
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
                }

                const cities = await response.json();
                console.log('Cities received:', cities);

                citySelect.innerHTML = '<option value="">Sélectionnez une ville</option>'; // Réinitialise
                if (Array.isArray(cities) && cities.length > 0) { // S'assurer que 'cities' est un tableau
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city;
                        option.textContent = city;
                        // Conserve la ville sélectionnée précédemment si elle correspond (utile en cas de re-validation)
                        if ("{{ old('city') }}" === city && selectedProvince === "{{ old('province') }}") {
                            option.selected = true;
                        }
                        citySelect.appendChild(option);
                    });
                } else {
                    citySelect.innerHTML = '<option value="">Aucune ville trouvée</option>';
                }

            } catch (error) {
                console.error('Error fetching cities:', error);
                citySelect.innerHTML = '<option value="">Erreur de chargement des villes</option>';
            }
        }

        // Écouteur d'événements pour le changement de province
        if (provinceSelect) {
            provinceSelect.addEventListener('change', function() {
                const selectedProvince = this.value;
                if (selectedProvince) {
                    loadCities(selectedProvince);
                } else {
                    citySelect.innerHTML = '<option value="">Sélectionnez une ville</option>'; // Si aucune province n'est sélectionnée
                }
            });
        } else {
            console.error('Element with ID "province" not found, cannot attach event listener.');
        }

        // Gestion de la valeur 'old' au chargement de la page
        // Si une province était déjà sélectionnée (par exemple après une erreur de validation), chargez les villes correspondantes
        if (provinceSelect && provinceSelect.value) {
            console.log('Old province value found on load:', provinceSelect.value);
            loadCities(provinceSelect.value);
        } else {
            console.log('No old province value found on load. City list remains empty initially.');
        }

        // Validation côté client pour le numéro de téléphone (reste inchangée)
        if (registerForm) {
            registerForm.addEventListener('submit', function(event) {
                phoneNumberInput.classList.remove('is-invalid');
                const existingError = phoneNumberInput.parentNode.querySelector('.invalid-feedback');
                if (existingError) {
                    existingError.remove();
                }

                if (phoneNumberInput.value.trim() !== '') {
                    const phoneNumber = phoneNumberInput.value.trim();
                    const phoneRegex = /^(?:\+243|0)[8-9]\d{8}$/;

                    if (!phoneRegex.test(phoneNumber)) {
                        event.preventDefault();
                        phoneNumberInput.classList.add('is-invalid');
                        const errorMessage = document.createElement('span');
                        errorMessage.classList.add('invalid-feedback');
                        errorMessage.setAttribute('role', 'alert');
                        errorMessage.innerHTML = '<strong>Veuillez entrer un numéro de téléphone RDC valide (ex: +24381xxxxxxx ou 081xxxxxxx).</strong>';
                        phoneNumberInput.parentNode.appendChild(errorMessage);
                    }
                }
            });
        }

        if (phoneNumberInput) {
            phoneNumberInput.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    this.classList.remove('is-invalid');
                    const existingError = phoneNumberInput.parentNode.querySelector('.invalid-feedback');
                    if (existingError) {
                        existingError.remove();
                    }
                }
            });
        }
    });
</script>
@endsection
