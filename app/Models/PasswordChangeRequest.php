<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_password_hash',
        'new_password_hash',
        'reason',
        'otp_code',
        'otp_verified',
        'admin_approved',
        'otp_expires_at',
        'admin_notified_at',
        'admin_approved_at',
        'status'
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'admin_notified_at' => 'datetime',
        'admin_approved_at' => 'datetime',
        'otp_verified' => 'boolean',
        'admin_approved' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}