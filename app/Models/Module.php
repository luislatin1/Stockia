<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'version',
        'enabled',
        'installed_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'installed_at' => 'datetime',
    ];
}

