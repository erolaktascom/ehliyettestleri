<?php
namespace Tests\Feature;

use App\Livewire\Admin\SeoSettings;
use App\Models\{SeoSetting, TopicCategory, LectureCategory, BlogCategory, TrafficSignCategory, User};
use App\Support\{SeoTemplates, SiteSitemap, AutomaticSlugRedirects};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CategorySeoRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_four_rules_save_separately_and_public_metadata_and_sitemaps_follow_only_their_own_rule(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $form = Livewire::test(SeoSettings::class);
        $models = ['topic_categories' => TopicCategory::class, 'lecture_categories' => LectureCategory::class,
            'blog_categories' => BlogCategory::class, 'traffic_sign_categories' => TrafficSignCategory::class];
        $sections = ['topic_categories' => 'soru-kategorileri', 'lecture_categories' => 'ders-kategorileri',
            'blog_categories' => 'blog-kategorileri', 'traffic_sign_categories' => 'isaret-kategorileri'];
        foreach ($models as $key => $model) {
            $form->assertSee(SeoTemplates::CATEGORY_TYPES[$key])->set("content_rules.$key.title", "$key %kategori%")
                ->set("content_rules.$key.description", "$key açıklaması");
        }
        $form->call('save')->assertHasNoErrors()->assertNoRedirect();
        $reopened = Livewire::test(SeoSettings::class);
        foreach ($models as $key => $model) {
            $reopened->assertSet("content_rules.$key.title", "$key %kategori%");
            $record = $model::firstOrFail();
            $response = $this->get(AutomaticSlugRedirects::publicPath($record))->assertOk();
            $response->assertSee('<title>'.$key.' '.e($record->name).'</title>', false)
                ->assertSee('content="'.$key.' açıklaması"', false);
        }
        foreach ($models as $key => $model) {
            $form->set("content_rules.$key.index", 'noindex')->call('save')->assertHasNoErrors();
            foreach ($models as $otherKey => $otherModel) {
                $record = $otherModel::firstOrFail();
                $this->get(AutomaticSlugRedirects::publicPath($record))->assertOk()
                    ->assertSee('content="'.($key === $otherKey ? 'noindex' : 'index').', follow"', false);
                $count = iterator_count((new SiteSitemap())->entries($sections[$otherKey]));
                if ($key === $otherKey) $this->assertSame(0, $count);
                else $this->assertGreaterThan(0, $count);
            }
            $record = $model::firstOrFail();
            $record->update(['seo_index' => 'index', 'seo_title' => 'Özel kategori başlığı']);
            $this->get(AutomaticSlugRedirects::publicPath($record))->assertOk()
                ->assertSee('<title>Özel kategori başlığı</title>', false)->assertSee('content="index, follow"', false);
            $this->assertGreaterThan(0, iterator_count((new SiteSitemap())->entries($sections[$key])));
            $form->set("content_rules.$key.index", 'index')->call('save')->assertHasNoErrors();
            $record->update(['seo_index' => 'inherit']);
        }
        $this->assertArrayNotHasKey('categories', SeoSetting::first()->content_rules);
    }

    public function test_existing_shared_rule_is_copied_without_overwriting_other_rules_or_timestamps(): void
    {
        $shared = ['title' => '%kategori% | %site_adi%', 'description' => 'Kategori %ozet%', 'index' => 'noindex'];
        $special = ['title' => 'Blog %baslik%', 'description' => '', 'index' => 'index'];
        $originalRules = ['categories' => $shared, 'blog_categories' => $special, 'pages' => ['title' => 'Sayfa']];
        $setting = SeoSetting::create(['content_rules' => $originalRules, 'verification_tags' => ['google-site-verification' => 'koru'], 'canonical_base' => 'https://example.com']);
        $before = (array) DB::table('seo_settings')->where('id', $setting->id)->first();
        $migration = require database_path('migrations/2026_11_02_180000_split_category_seo_rules.php');
        $migration->up();
        $rules = $setting->fresh()->content_rules;
        foreach (array_keys(SeoTemplates::CATEGORY_TYPES) as $key) $this->assertSame($key === 'blog_categories' ? $special : $shared, $rules[$key]);
        $this->assertSame(['title' => 'Sayfa'], $rules['pages']);
        $this->assertArrayNotHasKey('categories', $rules);
        $after = (array) DB::table('seo_settings')->where('id', $setting->id)->first();
        unset($before['content_rules'], $after['content_rules']);
        $this->assertSame($before, $after);
        $migration->up();
        $this->assertSame($rules, $setting->fresh()->content_rules);
        // Reads during deployment also preserve the old rule until migration finishes.
        $this->assertSame($shared, SeoTemplates::values($originalRules)['topic_categories']);
        $this->assertSame($special, SeoTemplates::values($originalRules)['blog_categories']);
    }
}
