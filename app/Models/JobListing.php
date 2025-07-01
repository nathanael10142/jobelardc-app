<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'salary',
        'user_id', // Si l'annonce est liée à un utilisateur
        'posted_by_name', // Si l'annonce peut être postée par un nom sans user_id direct
        'posted_by_type', // 'employer', 'candidate', 'admin', 'guest', etc.
    ];

    /**
     * Get the user that owns the job listing.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
