<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortLink extends Model
{
    protected $fillable = [
        'code',
        'destination_url',
        'title',
        'is_active',
        'clicks',
        'unique_clicks',
        'last_clicked_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'clicks' => 'integer',
            'unique_clicks' => 'integer',
            'last_clicked_at' => 'datetime',
        ];
    }

    public function clickLogs(): HasMany
    {
        return $this->hasMany(ShortLinkClick::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Limit a query to links the given user may see: everything for users with
     * the `shortlinks.view_all` ability (administrators, and super-admin via
     * Gate::before), otherwise only the links they created.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user && $user->can('shortlinks.view_all')) {
            return $query;
        }

        return $query->where('created_by', $user?->id);
    }

    /** The public short URL for this link. */
    public function getShortUrlAttribute(): string
    {
        return url('/'.$this->code);
    }
}
