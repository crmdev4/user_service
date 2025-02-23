<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailForgotPassword extends Model
{
    use HasFactory;

    protected $table = 'email_forgot_passwords';
    protected $fillable = ['user_id', 'token', 'expired_at'];
}
