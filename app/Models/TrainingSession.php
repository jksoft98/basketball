<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model {
    use HasFactory;
    protected $table    = 'training_sessions';
    protected $fillable = ['batch_id', 'created_by', 'session_date', 'session_time', 'session_type', 'notes'];
    protected $casts    = ['session_date' => 'date'];

    public function batch(): BelongsTo    { return $this->belongsTo(Batch::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class, 'session_id'); }

    public function scopeToday($query)  { return $query->whereDate('session_date', today()); }
    public function scopeRecent($query) { return $query->orderByDesc('session_date'); }

    public function presentCount(): int {
        return $this->attendances()->whereIn('status', ['present', 'late'])->count();
    }
    public function isAttendanceSaved(): bool { return $this->attendances()->exists(); }
}
