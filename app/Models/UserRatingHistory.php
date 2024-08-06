<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserRatingHistory extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user_ratings_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'evaluating_user_id',
        'assessed_user_id',
        'value',
    ];
}