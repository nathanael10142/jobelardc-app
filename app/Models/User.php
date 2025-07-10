<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Importez les modèles nécessaires pour les comptages
use App\Models\Message;
use App\Models\Call; // IMPORTANT: Assurez-vous d'importer le modèle Call
// Si vous avez un modèle pour les statuts, importez-le ici, ex:
// use App\Models\StatusUpdate;


class User extends Authenticatable // Ne pas implémenter MustVerifyEmail si vous voulez désactiver la vérification
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
     * Cette relation lie les messages où l'utilisateur est l'EXPÉDITEUR.
     * Elle utilise 'user_id' car c'est le nom de la colonne dans votre migration 'messages'.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'user_id'); // Utilise 'user_id' comme clé étrangère
    }

    /**
     * Un utilisateur peut recevoir plusieurs messages.
     * Cette relation lie les messages où l'utilisateur est le DESTINATAIRE.
     * Elle nécessite que la colonne 'receiver_id' existe dans votre table 'messages'.
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    // --- NOUVELLES RELATIONS POUR LES APPELS ---

    /**
     * Un utilisateur peut être l'appelant de plusieurs appels.
     */
    public function initiatedCalls(): HasMany
    {
        return $this->hasMany(Call::class, 'caller_id');
    }

    /**
     * Un utilisateur peut être le destinataire de plusieurs appels.
     */
    public function receivedCalls(): HasMany
    {
        return $this->hasMany(Call::class, 'receiver_id');
    }


    // --- NOUVELLES MÉTHODES POUR LES COMPTAGES NON LUS ---

    /**
     * Calcule le nombre total de messages non lus pour cet utilisateur.
     * Cette implémentation suppose que la table `messages` a une colonne `receiver_id`
     * et une colonne `read_at` qui est NULL si le message n'est pas lu.
     *
     * @return int
     */
    public function unreadMessagesCount(): int
    {
        // On compte les messages reçus par cet utilisateur qui n'ont pas de timestamp 'read_at'
        return $this->receivedMessages()->whereNull('read_at')->count();
    }

    /**
     * Calcule le nombre d'actualités/statuts non lus pour cet utilisateur.
     *
     * IMPORTANT : Cette méthode est un PLACEHOLDER.
     * Vous devrez l'adapter en fonction de la manière dont vous stockez
     * et suivez les statuts/actualités et leur lecture par les utilisateurs.
     *
     * @return int
     */
    public function unreadStatusUpdatesCount(): int
    {
        // Assurez-vous d'importer votre modèle StatusUpdate en haut si vous l'utilisez.
        // ex: use App\Models\StatusUpdate;

        // return StatusUpdate::where(...votre logique de non-lecture ici...)->count();
        return 0; // REMPLACER PAR VOTRE VRAIE LOGIQUE DE COMPTAGE DES STATUTS NON LUS
    }

    /**
     * Calcule le nombre d'appels manqués pour cet utilisateur.
     *
     * Cette implémentation suppose que:
     * - La table `calls` a une colonne `receiver_id` pour le destinataire de l'appel.
     * - La table `calls` a une colonne `status` qui peut prendre la valeur 'missed'.
     *
     * @return int
     */
    public function missedCallsCount(): int
    {
        // Compte les appels où l'utilisateur est le destinataire et le statut est 'missed'
        return $this->receivedCalls()->where('status', 'missed')->count();
    }

    // --- FIN NOUVELLES MÉTHODES ---


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