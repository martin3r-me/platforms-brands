<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für einzelne Typografie-Einträge (Schrift-Definitionen)
 */
class BrandsTypographyEntry extends Model implements HasDisplayName
{
    protected $table = 'brands_typography_entries';

    protected $fillable = [
        'uuid',
        'typography_board_id',
        'name',
        'role',
        'font_family',
        'font_source',
        'font_file_path',
        'font_file_name',
        'font_weight',
        'font_style',
        'font_size',
        'line_height',
        'letter_spacing',
        'text_transform',
        'sample_text',
        'order',
        'description',
    ];

    protected $casts = [
        'uuid' => 'string',
        'font_weight' => 'integer',
        'font_size' => 'decimal:2',
        'line_height' => 'decimal:2',
        'letter_spacing' => 'decimal:2',
        'order' => 'integer',
    ];

    public const ROLES = [
        'h1' => 'Headline 1',
        'h2' => 'Headline 2',
        'h3' => 'Headline 3',
        'h4' => 'Headline 4',
        'h5' => 'Headline 5',
        'h6' => 'Headline 6',
        'body' => 'Body',
        'body-sm' => 'Body Small',
        'caption' => 'Caption',
        'overline' => 'Overline',
        'subtitle' => 'Subtitle',
    ];

    public const FONT_WEIGHTS = [
        100 => 'Thin',
        200 => 'Extra Light',
        300 => 'Light',
        400 => 'Regular',
        500 => 'Medium',
        600 => 'Semi Bold',
        700 => 'Bold',
        800 => 'Extra Bold',
        900 => 'Black',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (!$model->order) {
                $maxOrder = self::where('typography_board_id', $model->typography_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function typographyBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsTypographyBoard::class, 'typography_board_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }

    public function getRoleLabelAttribute(): ?string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function getWeightLabelAttribute(): string
    {
        return self::FONT_WEIGHTS[$this->font_weight] ?? (string) $this->font_weight;
    }

    /**
     * CSS-Style-String für Live-Preview
     */
    public function getPreviewStyleAttribute(): string
    {
        $styles = [];
        $styles[] = "font-family: '{$this->font_family}', sans-serif";
        $styles[] = "font-weight: {$this->font_weight}";
        $styles[] = "font-style: {$this->font_style}";
        $styles[] = "font-size: {$this->font_size}px";

        if ($this->line_height) {
            $styles[] = "line-height: {$this->line_height}";
        }

        if ($this->letter_spacing !== null) {
            $styles[] = "letter-spacing: {$this->letter_spacing}px";
        }

        if ($this->text_transform) {
            $styles[] = "text-transform: {$this->text_transform}";
        }

        return implode('; ', $styles);
    }
}
