<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class BrandsIntakeBoard extends Model
{
    use LogsActivity;

    protected $table = 'brands_intake_boards';

    /**
     * Vereinfachtes Status-Modell: draft -> published -> closed
     *
     * - draft: Erhebung wird vorbereitet, nicht oeffentlich zugaenglich
     * - published: Erhebung ist live und nimmt Antworten entgegen
     * - closed: Erhebung ist beendet/archiviert
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Entwurf',
        self::STATUS_PUBLISHED => 'Veröffentlicht',
        self::STATUS_CLOSED => 'Geschlossen',
    ];

    protected $fillable = [
        'uuid',
        'brand_id',
        'name',
        'description',
        'status',
        'public_token',
        'ai_personality',
        'industry_context',
        'ai_instructions',
        'order',
        'is_active',
        'user_id',
        'team_id',
        'started_at',
        'completed_at',
        'done',
        'done_at',
    ];

    protected $casts = [
        'ai_instructions' => 'array',
        'is_active' => 'boolean',
        'done' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'done_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());

                $model->uuid = $uuid;
            }
        });
    }

    /**
     * Beziehungen
     */
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

    public function boardBlocks(): HasMany
    {
        return $this->hasMany(BrandsIntakeBoardBlock::class, 'intake_board_id')->orderBy('sort_order');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(BrandsIntakeSession::class, 'intake_board_id');
    }

    /**
     * Veroeffentlicht die Erhebung (ein Klick = live).
     * Setzt status, is_active und started_at automatisch.
     */
    public function publish(): self
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->is_active = true;

        if (empty($this->started_at)) {
            $this->started_at = now();
        }

        if (empty($this->public_token)) {
            $this->public_token = bin2hex(random_bytes(16));
        }

        $this->save();

        return $this;
    }

    /**
     * Schliesst die Erhebung.
     * Setzt status, is_active und completed_at automatisch.
     */
    public function close(): self
    {
        $this->status = self::STATUS_CLOSED;
        $this->is_active = false;

        if (empty($this->completed_at)) {
            $this->completed_at = now();
        }

        $this->save();

        return $this;
    }

    /**
     * Setzt die Erhebung zurueck auf Entwurf.
     */
    public function unpublish(): self
    {
        $this->status = self::STATUS_DRAFT;
        $this->is_active = false;
        $this->save();

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function generatePublicToken(): string
    {
        $this->public_token = bin2hex(random_bytes(16));
        $this->save();

        return $this->public_token;
    }

    public function getPublicUrl(): ?string
    {
        if (!$this->public_token) {
            return null;
        }

        return url('/brands/p/' . $this->public_token);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }
}
