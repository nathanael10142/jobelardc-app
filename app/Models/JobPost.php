<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPost extends Model
{
    use HasFactory;

    // Le nom de la table associée au modèle. Par défaut, Laravel infère 'job_posts'.
    // public $table = 'job_posts'; // Optionnel si tu suis les conventions de nommage de Laravel

    /**
     * Les attributs qui sont assignables en masse.
     * C'est essentiel pour la sécurité (Mass Assignment Protection).
     * @var array<int, string>
     */
    protected $fillable = [
        'title',          // Titre de l'offre d'emploi (ex: "Développeur Laravel Senior")
        'slug',           // Slug pour les URL SEO-friendly (ex: "developpeur-laravel-senior")
        'description',    // Description complète du poste
        'requirements',   // Exigences du poste (peut être du texte ou un JSON si plus complexe)
        'benefits',       // Avantages offerts (peut être du texte ou un JSON)
        'salary_min',     // Salaire minimum (peut être nullable)
        'salary_max',     // Salaire maximum (peut être nullable)
        'salary_currency',// Devise du salaire (ex: USD, CDF)
        'job_type',       // Type d'emploi (ex: 'Full-time', 'Part-time', 'Contract', 'Internship')
        'location',       // Localisation du poste (ville, pays)
        'is_remote',      // Booléen : true si télétravail, false sinon
        'application_deadline', // Date limite pour postuler
        'status',         // Statut de l'offre (ex: 'active', 'inactive', 'closed', 'pending')
        'views_count',    // Nombre de vues de l'annonce
        'employer_id',    // Clé étrangère vers l'utilisateur (employeur) qui a créé l'annonce
        'category_id',    // Clé étrangère vers la catégorie de l'emploi (ex: IT, Finance)
    ];

    /**
     * Les attributs qui doivent être castés à des types natifs.
     * Utile pour les dates (Carbon instances) et les booléens.
     * @var array<string, string>
     */
    protected $casts = [
        'application_deadline' => 'datetime',
        'is_remote' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // 'requirements' => 'array', // Si tu stockes les exigences en JSON
        // 'benefits' => 'array',     // Si tu stockes les avantages en JSON
    ];

    // --- Relations Eloquent ---

    /**
     * Une offre d'emploi appartient à une catégorie.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Une offre d'emploi est publiée par un employeur (User).
     */
    public function employer(): BelongsTo
    {
        // Assurez-vous que le modèle User est utilisé ici.
        // Vous pouvez ajouter une condition sur le rôle si nécessaire via un scope ou un middleware
        // pour vous assurer que seul un 'employer' peut être associé.
        return $this->belongsTo(User::class, 'employer_id');
    }

    /**
     * Une offre d'emploi peut avoir plusieurs candidatures.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    // --- Scopes (méthodes utiles pour requêter) ---

    /**
     * Scope pour récupérer les offres d'emploi actives.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('application_deadline', '>', now());
    }

    /**
     * Scope pour rechercher par titre ou description.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('title', 'like', '%' . $term . '%')
                     ->orWhere('description', 'like', '%' . $term . '%');
    }

    /**
     * Scope pour filtrer par type d'emploi.
     */
    public function scopeJobType($query, $type)
    {
        return $query->where('job_type', $type);
    }

    // --- Mutateurs et Accesseurs (optionnel) ---

    /**
     * Accesseur pour récupérer le salaire formaté.
     */
    // public function getFormattedSalaryAttribute()
    // {
    //     if ($this->salary_min && $this->salary_max) {
    //         return "{$this->salary_min} - {$this->salary_max} {$this->salary_currency}";
    //     } elseif ($this->salary_min) {
    //         return "À partir de {$this->salary_min} {$this->salary_currency}";
    //     }
    //     return "Non spécifié";
    // }

    // --- Événements du modèle (optionnel, pour générer le slug automatiquement) ---

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobPost) {
            $jobPost->slug = \Illuminate\Support\Str::slug($jobPost->title);
        });

        static::updating(function ($jobPost) {
            // Regénère le slug seulement si le titre a changé
            if ($jobPost->isDirty('title')) {
                $jobPost->slug = \Illuminate\Support\Str::slug($jobPost->title);
            }
        });
    }
}
