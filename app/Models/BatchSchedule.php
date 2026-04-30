<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'day_of_week',
        'session_time',
        'session_type',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'day_of_week' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    /**
     * Full day name e.g. "Monday"
     */
    public function getDayNameAttribute(): string
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ][$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Short day name e.g. "Mon"
     */
    public function getDayShortAttribute(): string
    {
        return substr($this->day_name, 0, 3);
    }

    /**
     * Formatted time e.g. "4:00 PM"
     */
    public function getFormattedTimeAttribute(): string
    {
        return \Carbon\Carbon::createFromFormat('H:i:s', $this->session_time)
            ->format('g:i A');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
