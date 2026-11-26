<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (DB::table('admin_roles')->get(['id', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true);
            if (is_array($permissions) && in_array('system-update', $permissions, true)) {
                DB::table('admin_roles')->where('id', $role->id)->update([
                    'permissions' => json_encode(array_values(array_diff($permissions, ['system-update'])), JSON_UNESCAPED_UNICODE),
                ]);
            }
        }
    }

    public function down(): void {}
};
