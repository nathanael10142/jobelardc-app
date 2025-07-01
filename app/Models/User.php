<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // <--- AJOUTEZ CETTE LIGNE

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'phone_number',
        'bio',
        'profile_picture',
        'location',
        'user_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // --- Relations Eloquent ---

    /**
     * Un utilisateur (candidat) peut avoir plusieurs candidatures.
     */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'user_id');
    }

    /**
     * Un utilisateur (employeur) peut créer plusieurs offres d'emploi.
     */
    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class, 'employer_id');
    }

    /**
     * Un utilisateur peut publier plusieurs annonces de marché (JobListing).
     */
    public function jobListings(): HasMany
    {
        return $this->hasMany(JobListing::class, 'user_id');
    }

    // --- NOUVELLES RELATIONS POUR LE CHAT ---

    /**
     * Un utilisateur peut participer à plusieurs conversations.
     * Utilise la table pivot 'conversation_user'.
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user');
    }

    /**
     * Un utilisateur peut envoyer plusieurs messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // --- FIN NOUVELLES RELATIONS ---


    /**
     * Accesseur pour obtenir les initiales de l'utilisateur.
     * @return string
     */
    public function getInitialsAttribute(): string
    {
        // Nettoie le nom pour enlever les espaces multiples et le couper
        $nameParts = explode(' ', trim($this->name));
        $initials = '';

        if (count($nameParts) > 1) {
            // Prend la première lettre du premier mot et la première lettre du dernier mot
            $initials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
        } elseif (!empty($nameParts[0])) {
            // Si un seul mot, prend les deux premières lettres
            $initials = strtoupper(substr($nameParts[0], 0, 2));
        }

        return $initials ?: 'NN'; // Retourne 'NN' si le nom est vide
    }

    /**
     * Accesseur pour obtenir une couleur d'arrière-plan aléatoire basée sur le nom de l'utilisateur.
     * Ceci est utile pour les avatars par défaut.
     * @return string (une couleur hexadécimale)
     */
    public function getAvatarBgColorAttribute(): string
    {
        $colors = [
            '#26a69a', '#ef5350', '#ab47bc', '#66bb6a', '#ff7043',
            '#42a5f5', '#ffee58', '#8d6e63', '#29b6f6', '#7e57c2'
        ];
        // Utilise une fonction de hachage simple pour obtenir une couleur "stable" pour un même nom
        $index = crc32($this->name) % count($colors);
        return $colors[$index];
    }
}
