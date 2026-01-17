<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Facebook Pages (übergeordnet auf User/Team-Ebene)
 * 
 * Ein User/Team kann Facebook Pages haben, die dann mit Brands verknüpft werden können
 */
class FacebookPage extends Model implements HasDisplayName
{
    protected $table = 'facebook_pages';

    protected $fillable = [
        'uuid',
        'external_id',
        'name',
        'description',
        'access_token',
        'refresh_token',
        'expires_at',
        'token_type',
        'scopes',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    /**
     * Services, die diese Facebook Page verwenden (Many-to-Many über core_service_assets)
     * z.B. BrandsBrand, CommsChannel, etc.
     */
    public function services()
    {
        return $this->morphedByMany(
            Model::class,
            'asset',
            'core_service_assets',
            'asset_id',
            'service_id'
        )->where('core_service_assets.asset_type', static::class)
         ->withTimestamps();
    }

    /**
     * Instagram Accounts, die mit dieser Facebook Page verknüpft sind
     */
    public function instagramAccounts(): HasMany
    {
        return $this->hasMany(InstagramAccount::class, 'facebook_page_id');
    }

    /**
     * Facebook Posts dieser Page
     */
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
