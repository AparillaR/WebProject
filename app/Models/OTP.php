<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'used',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];
    
}