<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\AdminAccess;
use Illuminate\Validation\ValidationException;

class AdminRole extends Model
{
    protected $fillable = ['name', 'key', 'permissions'];
    protected $casts = ['permissions' => 'array'];

    protected static function booted(): void
    {
        static::saving(function (AdminRole $role) {
            if (!in_array($role->key, ['admin', 'editor'], true)
                || ($role->exists && $role->isDirty('key'))) {
                throw ValidationException::withMessages(['key' => 'Yalnızca Yönetici ve Editör rolleri kullanılabilir.']);
            }
            $role->name = $role->key === 'admin' ? 'Yönetici' : 'Editör';
            $role->permissions = $role->key === 'admin' ? [] : array_values(array_unique(array_intersect(
                $role->permissions ?? [], array_keys(AdminAccess::editorModules())
            )));
        });
        static::deleting(function () {
            throw ValidationException::withMessages(['role' => 'Sistem rolleri silinemez.']);
        });
    }
}
