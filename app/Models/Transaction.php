<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'job_id',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'description',
    ];

    // Une transaction appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Une transaction peut être liée à un job (nullable car une transaction peut être pour un abonnement premium par exemple)
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
