<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Facebook Pages
 * 
 * Vollständig unabhängiges Model - erbt direkt von Laravel Model
 */
class BrandsFacebookPage extends Model implements HasDisplayName
{
    protected $table = 'brands_facebook_pages';

    protected $fillable = [
        'uuid',
        'brand_id',
        'external_id',
        'name',
        'description',
        'access_token',
        'refresh_token',
        'expires_at',
        'token_type',
        'scopes',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'expires_at' => 'datetime',
        'scopes' => 'array',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(BrandsBrand::class, 'brand_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function instagramAccounts(): HasMany
    {
        return $this->hasMany(BrandsInstagramAccount::class, 'facebook_page_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BrandsFacebookPost::class, 'facebook_page_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }

    /**
     * Verschlüsselt den Access Token beim Speichern
     */
    public function setAccessTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['access_token'] = Crypt::encryptString($value);
        } else {
            $this->attributes['access_token'] = null;
        }
    }

    /**
     * Entschlüsselt den Access Token beim Abrufen
     */
    public function getAccessTokenAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verschlüsselt den Refresh Token beim Speichern
     */
    public function setRefreshTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['refresh_token'] = Crypt::encryptString($value);
        } else {
            $this->attributes['refresh_token'] = null;
        }
    }

    /**
     * Entschlüsselt den Refresh Token beim Abrufen
     */
    public function getRefreshTokenAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Prüft ob Token abgelaufen ist
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return $this->expires_at->isPast();
    }

    /**
     * Prüft ob Token bald abläuft (innerhalb der nächsten 5 Minuten)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return $this->expires_at->isBefore(now()->addMinutes(5));
    }
}
