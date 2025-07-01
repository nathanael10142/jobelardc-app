<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <-- Importe BelongsTo

class JobApplication extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',      // L'ID du candidat (utilisateur)
        'job_post_id',  // L'ID de l'offre d'emploi à laquelle l'utilisateur postule
        'status',       // Statut de la candidature (ex: 'pending', 'reviewed', 'accepted', 'rejected')
        'cover_letter', // Lettre de motivation (texte)
        'resume_path',  // Chemin vers le CV téléchargé
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // --- Relations Eloquent ---

    /**
     * Une candidature appartient à un utilisateur (candidat).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Une candidature appartient à une offre d'emploi.
     */
    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class);
    }
}
