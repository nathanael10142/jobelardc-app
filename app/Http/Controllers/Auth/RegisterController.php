<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request; // Import the Request class
use Illuminate\Support\Facades\Auth; // Import Auth facade

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     * We'll use a dynamic redirectTo() method instead of a static property.
     *
     * @var string
     */
    // protected $redirectTo = '/dashboard'; // Comment or remove this line

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get the post-registration redirect path.
     * This method ensures users are redirected to their appropriate dashboard after standard registration.
     *
     * @return string
     */
    protected function redirectTo()
    {
        $user = Auth::user();

        // If the user is a 'super_admin' or 'admin', redirect to '/admin/dashboard'
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return route('admin.dashboard'); // Assuming you have this named route
        }

        // For 'candidate' and 'employer', redirect to 'candidates.dashboard'
        if ($user->hasRole('candidate') || $user->hasRole('employer')) {
            return route('listings.index'); // Ensure this route exists and is named 'candidates.dashboard'
        }

        // Default redirection if no specific role is found
        return '/home'; // Or any other default route
    }

    /**
     * Provides the centralized list of provinces and their associated cities.
     * This method is static to be easily accessible from other parts of the code.
     *
     * @return array The structure is an associative array: ['ProvinceName' => ['City1', 'City2'], ...]
     */
    public static function getProvincesAndCitiesData(): array
    {
        return [
            'Kinshasa' => ['Kinshasa'],
            'Kongo Central' => ['Matadi', 'Boma', 'Moanda', 'Luozi', 'Mbanza-Ngungu'],
            'Kwilu' => ['Kikwit', 'Bandundu-Ville', 'Gungu', 'Idiofa', 'Bulungu'],
            'Mai-Ndombe' => ['Inongo', 'Kutu', 'Bolo', 'Kiri', 'Oshwe'],
            'Kasaï' => ['Tshikapa', 'Ilebo', 'Mweka', 'Luiza', 'Demba'],
            'Kasaï Central' => ['Kananga', 'Kazumba', 'Dimbelenge', 'Luambo', 'Demba'],
            'Kasaï Oriental' => ['Mbuji-Mayi', 'Mwene-Ditu', 'Kabinda', 'Ngandajika', 'Lubao'],
            'Lomami' => ['Kabinda', 'Lubao', 'Gandajika', 'Luputa', 'Kamina'],
            'Sankuru' => ['Lusambo', 'Lodja', 'Kole', 'Lubefu', 'Monkoto'],
            'Maniema' => ['Kindu', 'Kasongo', 'Kibombo', 'Kailo', 'Pangi'],
            'Sud-Kivu' => ['Bukavu', 'Uvira', 'Baraka', 'Fizi', 'Kamituga', 'Shabunda'],
            'Nord-Kivu' => ['Goma', 'Butembo', 'Beni', 'Oicha', 'Rutshuru', 'Masisi', 'Walikale'],
            'Ituri' => ['Bunia', 'Aru', 'Mahagi', 'Djugu', 'Irumu', 'Mambasa'],
            'Haut-Uele' => ['Isiro', 'Watsa', 'Dungu', 'Faradje', 'Niangara'],
            'Bas-Uele' => ['Buta', 'Aketi', 'Bambesa', 'Ango', 'Bondo'],
            'Tshopo' => ['Kisangani', 'Banalia', 'Opala', 'Yangambi', 'Isangi'],
            'Mongala' => ['Lisala', 'Bumba', 'Bongandanga', 'Monkoto'],
            'Nord-Ubangi' => ['Gbadolite', 'Mobayi-Mbongo', 'Yakoma', 'Bosobolo', 'Businga'],
            'Sud-Ubangi' => ['Gemena', 'Zongo', 'Budjala', 'Libenge', 'Kung', 'Kungu'],
            'Équateur' => ['Mbandaka', 'Basankusu', 'Bikoro', 'Bolomba', 'Lukolela'],
            'Tshuapa' => ['Boende', 'Ikela', 'Bokungu', 'Monkoto', 'Befale'],
            'Tanganyika' => ['Kalemie', 'Nyunzu', 'Kabalo', 'Kongolo', 'Manono', 'Moba'],
            'Haut-Lomami' => ['Kamina', 'Kabongo', 'Malemba-Nkulu', 'Luena', 'Bukama'],
            'Lualaba' => ['Kolwezi', 'Dilolo', 'Mutshatsha', 'Kasaji', 'Kapanga'],
            'Haut-Katanga' => ['Lubumbashi', 'Likasi', 'Kasumbalesa', 'Kipushi', 'Sakania', 'Pweto'],
        ];
    }

    /**
     * Show the application registration form.
     * Passes only province names to the view. Cities will be loaded via AJAX.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        $provincesVilles = self::getProvincesAndCitiesData(); // Use self:: to call the static method
        $provinces = array_keys($provincesVilles); // Get only province names (keys)

        return view('auth.register', compact('provinces'));
    }

    /**
     * Handles the AJAX request to get cities for a given province.
     * Returns an array of cities in JSON format.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCitiesByProvince(Request $request)
    {
        $provinceName = $request->input('province');
        $provincesVilles = self::getProvincesAndCitiesData(); // Use self::

        // Return cities for the requested province, or an empty array if not found.
        $cities = $provincesVilles[$provinceName] ?? [];

        return response()->json($cities);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $provincesVilles = self::getProvincesAndCitiesData(); // Use self::
        $allProvinces = array_keys($provincesVilles);

        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^(\+243|0)[8-9]\d{8}$/'],
            'province' => ['required', 'string', 'max:255', 'in:' . implode(',', $allProvinces)],
            'city' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($data, $provincesVilles) {
                    $selectedProvince = $data['province'];
                    // Check if the province exists and if the city is in that province's list
                    if (!isset($provincesVilles[$selectedProvince]) || !in_array($value, $provincesVilles[$selectedProvince])) {
                        $fail("La ville sélectionnée n'est pas valide pour la province choisie.");
                    }
                },
            ],
            'user_type' => ['required', 'string', 'in:candidate,employer'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $profilePicturePath = null;
        if (isset($data['profile_picture'])) {
            $profilePicturePath = $data['profile_picture']->store('profile_pictures', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone_number' => $data['phone_number'] ?? null,
            'bio' => $data['bio'] ?? null,
            'profile_picture' => $profilePicturePath,
            'location' => $data['city'] . ', ' . $data['province'],
            'user_type' => $data['user_type'],
        ]);

        $roleName = $data['user_type'];
        $assignedRole = Role::where('name', $roleName)->first();

        if ($assignedRole) {
            $user->assignRole($assignedRole);
        } else {
            \Log::warning('Le rôle "' . $roleName . '" n\'a pas été trouvé. Tentative d\'assignation du rôle "user" par défaut.');
            $fallbackRole = Role::where('name', 'user')->first();
            if ($fallbackRole) {
                $user->assignRole($fallbackRole);
            } else {
                \Log::error('Le rôle "user" par défaut n\'a pas été trouvé. Veuillez vous assurer que les rôles sont configurés dans votre application.');
            }
        }

        event(new Registered($user));

        return $user;
    }
}
