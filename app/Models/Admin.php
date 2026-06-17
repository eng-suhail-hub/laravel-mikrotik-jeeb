<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════════
 *  موديل الأدمن
 * ════════════════════════════════════════════════════════════════
 *
 *  يستخدم Session Guard العادي لحماية لوحة التحكم.
 *  منفصل عن User (العملاء) تماماً.
 */
class Admin extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'admins';

    protected $fillable = [
        'username',
        'password',
        'full_name',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];
}
