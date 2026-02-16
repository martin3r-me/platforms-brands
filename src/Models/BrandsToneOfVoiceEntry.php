<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für einzelne Messaging-Einträge (Slogan, Elevator Pitch, Kernbotschaft, Wert, Claim)
 */
class BrandsToneOfVoiceEntry extends Model implements HasDisplayName
{
    protected $table = 'brands_tone_of_voice_entries';

    protected $fillable = [
        'uuid',
        'tone_of_voice_board_id',
        'name',
        'type',
        'content',
        'description',
        'example_positive',
        'example_negative',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
        'order' => 'integer',
    ];

    public const TYPES = [
        'slogan' => 'Slogan',
        'elevator_pitch' => 'Elevator Pitch',
        'core_message' => 'Kernbotschaft',
        'value' => 'Wert',
        'claim' => 'Claim',
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

    public function getTypeLabelAttribute(): ?string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
