<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Core\Traits\Encryptable;
use Symfony\Component\Uid\UuidV7;

class BrandsIntakeSession extends Model
{
    use Encryptable;

    protected $table = 'brands_intake_sessions';

    protected $fillable = [
        'uuid',
        'session_token',
        'intake_board_id',
        'status',
        'answers',
        'respondent_name',
        'respondent_email',
        'current_step',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected array $encryptable = [
        'answers' => 'json',
        'metadata' => 'json',
        'respondent_name' => 'string',
        'respondent_email' => 'string',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

            if (empty($model->session_token)) {
                $model->session_token = self::generateShortToken();
            }

            if (empty($model->started_at)) {
                $model->started_at = now();
            }
        });
    }

    /**
     * Generiert einen kurzen, lesbaren Session-Token im Format XXXX-XXXX.
     * Verwendet 30 Zeichen (ohne I, O, L um Verwechslungen zu vermeiden).
     */
    public static function generateShortToken(): string
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $part1 = '';
            $part2 = '';
            for ($i = 0; $i < 4; $i++) {
                $part1 .= $chars[random_int(0, 29)];
                $part2 .= $chars[random_int(0, 29)];
            }
            $token = $part1 . '-' . $part2;
        } while (self::where('session_token', $token)->exists());

        return $token;
    }

    /**
     * Beziehungen
     */
    public function intakeBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsIntakeBoard::class, 'intake_board_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(BrandsIntakeStep::class, 'session_id');
    }
}
