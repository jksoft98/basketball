<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Student extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = [
    'batch_id', 'full_name', 'age', 'parent_name', 'parent_contact',
    'photo_path', 'photo_thumb_path', 'jersey_number', 'position',
    'skill_level', 'injury_status', 'is_active', 'joined_at',
    ];
    protected $casts = ['is_active' => 'boolean', 'joined_at' => 'date'];

    public function batch(): BelongsTo { return $this->belongsTo(Batch::class); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeFit($query)    { return $query->where('injury_status', 'fit'); }

    public function getPhotoUrlAttribute(): string {
        return Storage::disk('public')->url($this->photo_path);
    }
    public function getThumbUrlAttribute(): string {
        return $this->photo_thumb_path
            ? Storage::disk('public')->url($this->photo_thumb_path)
            : Storage::disk('public')->url($this->photo_path);
    }

    public function attendancePercentage(): float
    {
        $query = $this->attendances();

        // Only count sessions from joining date onwards
        if ($this->joined_at) {
            $query = $query->whereHas('session', function ($q) {
                $q->whereDate('session_date', '>=', $this->joined_at);
            });
        }

        // Only count sessions before the student was deleted
        if ($this->deleted_at) {
            $query = $query->whereHas('session', function ($q) {
                $q->whereDate('session_date', '<=', $this->deleted_at);
            });
        }

        $total = $query->count();
        if ($total === 0) return 100.0;

        $present = (clone $query)
            ->whereIn('status', ['present', 'late'])
            ->count();

        return round(($present / $total) * 100, 1);
    }

    public function isAtRisk(): bool
    {
        // A deleted student is never flagged as at-risk
        if ($this->deleted_at) return false;

        $query = $this->attendances();

        if ($this->joined_at) {
            $query = $query->whereHas('session', function ($q) {
                $q->whereDate('session_date', '>=', $this->joined_at);
            });
        }

        if ($query->count() < 3) return false;

        return $this->attendancePercentage() < 75;
    }
}
