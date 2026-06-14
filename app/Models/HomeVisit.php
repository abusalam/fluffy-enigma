<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeVisit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'visitor_id',
        'is_unique',
        'ip_hash',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_unique' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
