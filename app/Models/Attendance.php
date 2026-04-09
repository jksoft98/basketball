<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model {
    use HasFactory;
    protected $table    = 'attendance';
    protected $fillable = ['session_id', 'student_id', 'status', 'note', 'marked_at'];
    protected $casts    = ['marked_at' => 'datetime'];

    public function session(): BelongsTo { return $this->belongsTo(TrainingSession::class, 'session_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function scopePresent($query) { return $query->whereIn('status', ['present', 'late']); }
}
