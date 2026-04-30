<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'coach_id', 'name', 'description', 'skill_level', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    /**
     * All schedules (active + inactive — for admin management)
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(BatchSchedule::class)->orderBy('day_of_week');
    }

    /**
     * Only active schedules (used for session generation)
     */
    public function activeSchedules(): HasMany
    {
        return $this->hasMany(BatchSchedule::class)
            ->where('is_active', true)
            ->orderBy('day_of_week');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function activeStudentCount(): int
    {
        return $this->students()->where('is_active', true)->count();
    }

    /**
     * Human-readable schedule summary e.g. "Mon 4:00 PM, Wed 4:00 PM, Sat 10:00 AM"
     */
    public function getScheduleSummaryAttribute(): string
    {
        $schedules = $this->activeSchedules;

        if ($schedules->isEmpty()) {
            return 'No schedule set';
        }

        return $schedules->map(fn($s) => $s->day_short.' '.$s->formatted_time)->join(', ');
    }
}
