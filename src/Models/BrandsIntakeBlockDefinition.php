<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class BrandsIntakeBlockDefinition extends Model
{
    use LogsActivity;

    protected $table = 'brands_intake_block_definitions';

    /**
     * Alle verfuegbaren Block-Typen
     */
    public const BLOCK_TYPES = [
        'text',
        'long_text',
        'email',
        'phone',
        'url',
        'select',
        'multi_select',
        'number',
        'scale',
        'date',
        'boolean',
        'file',
        'rating',
        'location',
        'info',
        'custom',
    ];

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'block_type',
        'ai_prompt',
        'conditional_logic',
        'response_format',
        'fallback_questions',
        'validation_rules',
        'logic_config',
        'ai_behavior',
        'min_confidence_threshold',
        'max_clarification_attempts',
        'is_active',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'conditional_logic' => 'array',
        'response_format' => 'array',
        'fallback_questions' => 'array',
        'validation_rules' => 'array',
        'logic_config' => 'array',
        'ai_behavior' => 'array',
        'min_confidence_threshold' => 'decimal:2',
        'max_clarification_attempts' => 'integer',
        'is_active' => 'boolean',
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
        return $this->hasMany(BrandsIntakeBoardBlock::class, 'block_definition_id');
    }

    public function intakeSteps(): HasMany
    {
        return $this->hasMany(BrandsIntakeStep::class, 'block_definition_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('block_type', $type);
    }

    /**
     * Hilfsmethoden fuer Block-Typen
     */
    public static function getBlockTypes(): array
    {
        return [
            'text' => 'Text-Eingabe',
            'long_text' => 'Langer Text / Freitext',
            'email' => 'E-Mail Adresse',
            'phone' => 'Telefonnummer',
            'url' => 'URL / Webadresse',
            'select' => 'Auswahl (Single)',
            'multi_select' => 'Auswahl (Multiple)',
            'number' => 'Zahl',
            'scale' => 'Skala (1-10, 1-5 etc.)',
            'date' => 'Datum',
            'boolean' => 'Ja/Nein',
            'file' => 'Datei-Upload',
            'rating' => 'Bewertung',
            'location' => 'Standort',
            'info' => 'Info / Hinweis (ohne Eingabe)',
            'custom' => 'Benutzerdefiniert',
        ];
    }

    public function getBlockTypeLabel(): string
    {
        return self::getBlockTypes()[$this->block_type] ?? $this->block_type;
    }
}
