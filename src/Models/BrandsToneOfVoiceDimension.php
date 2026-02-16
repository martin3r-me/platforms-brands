<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Tone-Dimensionen (z.B. formell ↔ locker, ernst ↔ humorvoll)
 */
class BrandsToneOfVoiceDimension extends Model implements HasDisplayName
{
    protected $table = 'brands_tone_of_voice_dimensions';

    protected $fillable = [
        'uuid',
        'tone_of_voice_board_id',
        'name',
        'label_left',
        'label_right',
        'value',
        'description',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
        'value' => 'integer',
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (!$model->order) {
                $maxOrder = self::where('tone_of_voice_board_id', $model->tone_of_voice_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function toneOfVoiceBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsToneOfVoiceBoard::class, 'tone_of_voice_board_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}
