<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_roles', function (Blueprint $table) {
            $table->json('permissions')->nullable();
        });

        $defaults = ['question-topics', 'questions', 'question-imports', 'exams', 'topic-tests',
            'content-categories', 'topics', 'pages', 'traffic-sign-categories', 'traffic-signs',
            'blog-categories', 'blog-posts'];
        DB::transaction(function () use ($defaults) {
            DB::table('admin_roles')->updateOrInsert(['key' => 'admin'], [
                'name' => 'Yönetici', 'is_super_admin' => true, 'is_system' => true,
                'permissions_count' => 0, 'permissions' => json_encode([]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('admin_roles')->updateOrInsert(['key' => 'editor'], [
                'name' => 'Editör', 'is_super_admin' => false, 'is_system' => true,
                'permissions_count' => count($defaults), 'permissions' => json_encode($defaults),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('admin_roles')->whereNotIn('key', ['admin', 'editor'])->delete();
            DB::table('users')->where('is_admin', true)->orderBy('id')->get()->each(function ($user) {
                $isEditor = in_array(\Illuminate\Support\Str::slug($user->role_name ?? ''), ['editor', 'icerik-editoru', 'content-editor'], true);
                DB::table('users')->where('id', $user->id)->update(['role_name' => $isEditor ? 'Editör' : 'Yönetici']);
            });
            DB::table('users')->where('is_admin', false)->update(['role_name' => 'Öğrenci']);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('author_name')->default('EhliyetGo')->change();
        });
    }

    public function down(): void
    {
        Schema::table('admin_roles', fn (Blueprint $table) => $table->dropColumn('permissions'));
    }
};
