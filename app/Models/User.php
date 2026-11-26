<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\AdminAccess;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_admin',
        'role_name',
        'target_license_class',
        'driving_exam_date',
        'city',
        'birth_date',
        'premium_until',
        'last_active_at',
        'biography',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected static function booted(): void
    {
        static::created(function (User $user) {
            // Historical data migrations may create users before profile columns exist.
            if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'profile_slug')) return;
            $base = \Illuminate\Support\Str::limit(\Illuminate\Support\Str::slug($user->name), 180, '') ?: 'uye';
            // The primary key makes the collision fallback deterministic and unique.
            $slug = $base; $suffix = 0;
            $legacySlugs = \App\Models\BlogPost::whereDoesntHave('creator')->distinct()->pluck('author_name')
                ->filter()->map(fn ($name) => \Illuminate\Support\Str::slug($name))->all();
            while (in_array($slug, $legacySlugs, true) || static::where('profile_slug', $slug)->exists()) {
                $slug = $base.'-'.$user->id.($suffix++ ? '-'.$suffix : '');
            }
            $user->profile_slug = $slug;
            $user->saveQuietly();
        });
        static::saving(function (User $user) {
            if (!$user->is_admin) {
                $user->role_name = 'Öğrenci';
            } elseif (in_array($user->role_name, [null, '', 'Öğrenci'], true)) {
                $user->role_name = 'Yönetici';
            } elseif (!in_array($user->role_name, ['Yönetici', 'Editör'], true)) {
                throw ValidationException::withMessages(['role_name' => 'Yönetici veya Editör seçin.']);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'premium_until' => 'datetime',
            'last_active_at' => 'datetime',
            'driving_exam_date' => 'date',
            'birth_date' => 'date',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'created_by');
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name ?? ''));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= mb_substr($w, 0, 1);
        }
        return mb_strtoupper($initials) ?: 'Ö';
    }

    public function getDaysToExamAttribute(): ?int
    {
        if (!$this->driving_exam_date) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($this->driving_exam_date->startOfDay(), false);
    }

    public function isPremium(): bool
    {
        return $this->premium_until ? $this->premium_until->isFuture() : false;
    }

    public function isEditor(): bool
    {
        return $this->is_admin && $this->role_name === 'Editör';
    }

    public function isAdministrator(): bool
    {
        return $this->is_admin && $this->role_name === 'Yönetici';
    }

    public function adminModules(): array
    {
        if ($this->isAdministrator()) {
            return array_keys(AdminAccess::modules());
        }
        if (!$this->isEditor()) {
            return [];
        }
        $permissions = AdminRole::where('key', 'editor')->first()?->permissions ?? [];
        return array_values(array_intersect($permissions, array_keys(AdminAccess::editorModules())));
    }

    public function canAccessAdminRoute(?string $routeName): bool
    {
        if ($routeName === 'admin.access') {
            return $this->isAdministrator() || $this->isEditor();
        }
        return str_starts_with($routeName ?? '', 'admin.')
            && in_array(explode('.', $routeName)[1] ?? '', $this->adminModules(), true);
    }

    public function adminLandingRoute(): string
    {
        $modules = $this->adminModules();
        if (in_array('dashboard', $modules, true)) return 'admin.dashboard';
        if (in_array('blog-posts', $modules, true)) return 'admin.blog-posts';
        return isset($modules[0]) ? 'admin.'.$modules[0] : 'admin.access';
    }


    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function signReviews(): HasMany
    {
        return $this->hasMany(UserSignReview::class);
    }

    public function lectureProgress(): HasMany
    {
        return $this->hasMany(UserLectureProgress::class);
    }

    public function savedQuestions(): HasMany
    {
        return $this->hasMany(SavedQuestion::class);
    }
}
