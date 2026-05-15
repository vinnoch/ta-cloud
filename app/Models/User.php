<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'email', 'password', 'users_id', 'study_program_id', 'role', 'nim', 'nidn_nip'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $with = ['level'];

    public function level()
    {
        return $this->belongsTo(UserLevel::class, 'users_id', 'users_id');
    }


    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    public function skripsi(): HasOne
    {
        return $this->hasOne(Skripsi::class, 'student_id');
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

    public function finalDocumentApprovals(): HasMany
    {
        return $this->hasMany(FinalDocumentApproval::class, 'reviewer_id');
    }

    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('level', fn (Builder $relation): Builder => $relation->where('users_level', $role));
    }

    public function getRoleAttribute(): ?string
    {
        return $this->attributes['role'] ?? $this->level?->users_level;
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
                $level = UserLevel::where('users_level', $user->attributes['role'])->first();

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
            'password' => 'hashed',
        ];
    }
}
