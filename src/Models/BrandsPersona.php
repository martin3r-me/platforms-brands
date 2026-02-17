<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für einzelne Personas (Zielgruppen-Profile)
 */
class BrandsPersona extends Model implements HasDisplayName
{
    protected $table = 'brands_personas';

    protected $fillable = [
        'uuid',
        'persona_board_id',
        'name',
        'avatar_url',
        'age',
        'gender',
        'occupation',
        'location',
        'education',
        'income_range',
        'bio',
        'pain_points',
        'goals',
        'quotes',
        'behaviors',
        'channels',
        'brands_liked',
        'tone_of_voice_board_id',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
        'age' => 'integer',
        'pain_points' => 'array',
        'goals' => 'array',
        'quotes' => 'array',
        'behaviors' => 'array',
        'channels' => 'array',
        'brands_liked' => 'array',
        'order' => 'integer',
    ];

    public const GENDERS = [
        'female' => 'Weiblich',
        'male' => 'Männlich',
        'diverse' => 'Divers',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (!$model->order) {
                $maxOrder = self::where('persona_board_id', $model->persona_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function personaBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsPersonaBoard::class, 'persona_board_id');
    }

    public function toneOfVoiceBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsToneOfVoiceBoard::class, 'tone_of_voice_board_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }

    public function getGenderLabelAttribute(): ?string
    {
        return self::GENDERS[$this->gender] ?? $this->gender;
    }
}
