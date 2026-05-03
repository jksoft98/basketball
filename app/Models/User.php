<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable {
    use HasFactory, Notifiable;
    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['email_verified_at' => 'datetime', 'password' => 'hashed'];

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'batch_coach')
                    ->withTimestamps();
    }
    public function trainingSessions(): HasMany { return $this->hasMany(TrainingSession::class, 'created_by'); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isCoach(): bool { return $this->role === 'coach'; }
}
