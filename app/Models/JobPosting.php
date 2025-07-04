<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// RENOMME CETTE CLASSE DE 'Job' EN 'JobPosting'
class JobPosting extends Model // <-- CHANGEMENT MAJEUR ICI
{
    use HasFactory;

    // Ajoute cette ligne pour être explicite sur la table
    protected $table = 'job_postings'; // <-- NOUVELLE LIGNE AJOUTÉE

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'price',
        'price_type',
        'location',
        'contact_phone',
        'contact_email',
        'is_featured',
        'expires_at',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // Un job appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Un job appartient à une catégorie
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Un job peut avoir plusieurs transactions (si plusieurs boosts sont effectués sur la même annonce)
    public function transactions()
    {
        // Si ta transaction référence 'job_postings' maintenant, c'est bon.
        // Si elle référence encore un ancien 'Job', il faudra aussi la mettre à jour.
        // Ici, cela devrait pointer vers JobPosting::class si la relation est bien configurée.
        return $this->hasMany(Transaction::class);
    }
}