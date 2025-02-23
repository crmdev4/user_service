<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailUserVerification extends Model
{
    use HasFactory;

    protected $table = 'email_user_verifications';

    protected $fillable = ['user_id', 'token', 'expired_at'];
}
