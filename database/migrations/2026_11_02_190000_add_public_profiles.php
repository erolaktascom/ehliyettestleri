<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_slug')->nullable()->unique();
            $table->text('biography')->nullable();
            $table->string('avatar_path')->nullable();
        });
        $legacySlugs = DB::table('blog_posts')->leftJoin('users', 'users.id', '=', 'blog_posts.created_by')
            ->whereNull('users.id')->distinct()->pluck('author_name')->filter()->map(fn ($name) => Str::slug($name))->all();
        // Preserve established author addresses before allocating member addresses.
        foreach (DB::table('users')->orderByDesc('is_admin')->orderBy('id')->get() as $user) {
            $name = DB::table('blog_posts')->where('created_by', $user->id)->orderBy('id')->value('author_name') ?: $user->name;
            $base = Str::limit(Str::slug($name), 180, '') ?: 'uye';
            $slug = $base; $suffix = 2;
            while (in_array($slug, $legacySlugs, true) || DB::table('users')->where('profile_slug', $slug)->exists()) $slug = $base.'-'.$suffix++;
            DB::table('users')->where('id', $user->id)->update(['profile_slug' => $slug]);
        }
    }
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['profile_slug', 'biography', 'avatar_path']));
    }
};