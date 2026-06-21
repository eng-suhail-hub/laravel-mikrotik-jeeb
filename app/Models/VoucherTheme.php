<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherTheme extends Model
{
    protected $table = 'voucher_themes';

    protected $fillable = ['name', 'blade_view', 'thumbnail', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
