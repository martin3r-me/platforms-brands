<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Log;
use Platform\Organization\Traits\HasOrganizationContexts;
use Platform\Core\Traits\HasColors;
use Platform\Core\Contracts\HasTimeAncestors;
use Platform\Core\Contracts\HasKeyResultAncestors;
use Platform\Core\Contracts\HasDisplayName;

/**
 * @ai.description Marke dient als Container für Brand-Management im Team.
 */
class BrandsBrand extends Model implements HasTimeAncestors, HasKeyResultAncestors, HasDisplayName
{
    use HasOrganizationContexts, HasColors;

    protected $table = 'brands_brands';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'order',
        'user_id',
        'team_id',
        'company_id',
        'contact_id',
        'done',
        'done_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'done' => 'boolean',
        'done_at' => 'datetime',
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Platform\Crm\Models\CrmCompany::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(\Platform\Crm\Models\CrmContact::class);
    }

    /**
     * Gibt alle Vorfahren-Kontexte für die Zeitkaskade zurück.
     * Brand → Brand selbst (als Root)
     */
    public function timeAncestors(): array
    {
        return [];
    }

    /**
     * Gibt alle Vorfahren-Kontexte für die KeyResult-Kaskade zurück.
     * Brand → Brand selbst (als Root)
     */
    public function keyResultAncestors(): array
    {
        return [];
    }

    /**
     * Gibt den anzeigbaren Namen der Marke zurück.
     */
    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}
