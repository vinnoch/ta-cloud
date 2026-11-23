<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'users_id', 'study_program_id', 'role', 'nim', 'nidn_nip', 'google_id', 'google_avatar', 'account_status', 'registration_method', 'reviewed_by', 'reviewed_at', 'rejection_reason'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLES = ['superadmin', 'admin', 'kaprodi', 'dosen', 'mahasiswa'];

    use HasFactory, Notifiable, SoftDeletes;

    protected $with = ['level'];

    public function level()
    {
        return $this->belongsTo(UserLevel::class, 'users_id', 'users_id');
    }

    public function assignedRoles(): BelongsToMany
    {
        return $this->belongsToMany(UserLevel::class, 'user_roles', 'user_id', 'users_id')->withTimestamps();
    }

    /** @return array<int, string> */
    public function availableRoleNames(): array
    {
        $roles = $this->assignedRoles()->pluck('users_level')->all();

        return $roles !== [] ? $roles : array_values(array_filter([$this->attributes['role'] ?? $this->level?->users_level]));
    }

    public function hasAssignedRole(string $role): bool
    {
        return ($this->attributes['role'] ?? null) === $role
            || $this->assignedRoles()->where('users_level', $role)->exists();
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reviewed_by');
    }

    public function tugasAkhir(): HasOne
    {
        return $this->hasOne(TugasAkhir::class, 'student_id');
    }

    public function reviewerAssignments(): HasMany
    {
        return $this->hasMany(ReviewerAssignment::class, 'lecturer_id');
    }

    public function reviewedGrades(): HasMany
    {
        return $this->hasMany(Grade::class, 'reviewer_id');
    }

    public function sidangRequests(): HasMany
    {
        return $this->hasMany(SidangRequest::class, 'lecturer_id');
    }

    public function reviewedBimbingans(): HasMany
    {
        return $this->hasMany(Bimbingan::class, 'reviewer_id');
    }

    public function uploadedDocumentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'uploaded_by');
    }

    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where(function (Builder $query) use ($role): void {
            $query->where('role', $role)
                ->orWhereHas('assignedRoles', fn (Builder $relation): Builder => $relation->where('users_level', $role));
        });
    }

    public function getRoleAttribute(): ?string
    {
        $storedRole = $this->attributes['role'] ?? $this->level?->users_level;

        if (app()->bound('request') && request()->hasSession()) {
            $activeRole = request()->session()->get('active_role');

            if (is_string($activeRole) && ($activeRole === $storedRole || $this->hasAssignedRole($activeRole))) {
                return $activeRole;
            }
        }

        return $storedRole;
    }

    public function setRoleAttribute(string $value): void
    {
        $this->attributes['role'] = $value;

        $level = UserLevel::where('users_level', $value)->first();

        if ($level) {
            $this->attributes['users_id'] = $level->users_id;
        }
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if (! empty($user->attributes['role'])) {
                $level = in_array($user->attributes['role'], self::ROLES, true)
                    ? UserLevel::firstOrCreate(['users_level' => $user->attributes['role']])
                    : null;

                if ($level) {
                    $user->attributes['users_id'] = $level->users_id;
                }
            } elseif ($user->relationLoaded('level') && $user->level) {
                $user->attributes['role'] = $user->level->users_level;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
