<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortLinkClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'short_link_id',
        'visitor_id',
        'is_unique',
        'ip_hash',
        'referer',
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

    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class);
    }
}
