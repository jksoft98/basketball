<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model {
    use HasFactory;
    protected $fillable = ['coach_id', 'name', 'description', 'skill_level', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function coach(): BelongsTo { return $this->belongsTo(User::class, 'coach_id'); }
    public function students(): HasMany { return $this->hasMany(Student::class); }
    public function sessions(): HasMany { return $this->hasMany(TrainingSession::class); }
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function activeStudentCount(): int { return $this->students()->where('is_active', true)->count(); }
}
