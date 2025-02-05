<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'auth_code', 'expires_at'];
    public $timestamps = false;
    protected $primaryKey = 'email';
    public $incrementing = false;
}
