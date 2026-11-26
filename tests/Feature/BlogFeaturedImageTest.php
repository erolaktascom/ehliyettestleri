<?php

namespace Tests\Feature;

use App\Livewire\Admin\BlogPostForm;
use App\Models\{BlogCategory, BlogPost, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BlogFeaturedImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_cover_is_uploaded_displayed_preserved_replaced_and_removed(): void
    {
        $category = BlogCategory::create(['name' => 'Rehber', 'slug' => 'rehber']);
        Livewire::test(BlogPostForm::class)->set('blog_category_id', $category->id)
            ->set('title', 'Kapaklı yazı')->set('slug', 'kapakli-yazi')->set('content', '<p>Yazı içeriği</p>')
            ->set('featuredImageUpload', UploadedFile::fake()->image('cover.jpg', 800, 450))
            ->call('save')->assertHasNoErrors()->assertRedirect(route('admin.blog-posts'));
        $post = BlogPost::where('slug', 'kapakli-yazi')->firstOrFail();
        $path = $post->featured_image_path;
        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith('blog-images/', $path);
        $this->get(route('blog.index'))->assertOk()->assertSee($post->featured_image_url, false);
        $this->get(route('blog.show', $post))->assertOk()->assertSee($post->featured_image_url, false);
        $this->get($post->author_url)->assertOk()->assertSee($post->featured_image_url, false);
        Livewire::test(BlogPostForm::class, ['id' => $post->id])->assertSee('Öne Çıkan Görsel')
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertSame($path, $post->fresh()->featured_image_path);
        $firstPublication = $post->first_published_at;
        Livewire::test(BlogPostForm::class, ['id' => $post->id])
            ->set('featuredImageUpload', UploadedFile::fake()->image('new.png', 640, 360))
            ->call('save')->assertHasNoErrors()->assertNoRedirect()->assertSet('featuredImageUpload', null);
        $post->refresh();
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($post->featured_image_path);
        $this->assertNotNull($post->content_updated_at);
        $this->assertSame(auth()->id(), $post->last_edited_by);
        $this->assertTrue($firstPublication->equalTo($post->first_published_at));
        $replacement = $post->featured_image_path;
        Livewire::test(BlogPostForm::class, ['id' => $post->id])->set('removeFeaturedImage', true)
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertNull($post->fresh()->featured_image_path);
        Storage::disk('public')->assertMissing($replacement);
    }

    public function test_invalid_covers_are_rejected_without_saving_files(): void
    {
        foreach ([
            UploadedFile::fake()->createWithContent('bad.png', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            UploadedFile::fake()->image('large.jpg')->size(2049),
            UploadedFile::fake()->image('wide.png', 4097, 10),
        ] as $file) {
            Livewire::test(BlogPostForm::class)->set('featuredImageUpload', $file)->assertHasErrors('featuredImageUpload');
        }
        $this->assertSame([], Storage::disk('public')->allFiles('blog-images'));
    }

    public function test_delete_does_not_remove_shared_covers_or_unrelated_paths(): void
    {
        Storage::disk('public')->put('blog-images/shared.png', 'image');
        $first = BlogPost::create(['title' => 'Bir', 'slug' => 'bir', 'featured_image_path' => 'blog-images/shared.png']);
        $second = BlogPost::create(['title' => 'İki', 'slug' => 'iki', 'featured_image_path' => 'blog-images/shared.png']);
        $first->delete();
        Storage::disk('public')->assertExists('blog-images/shared.png');
        $second->delete();
        Storage::disk('public')->assertMissing('blog-images/shared.png');
        Storage::disk('public')->put('question-image/keep.png', 'image');
        $other = BlogPost::create(['title' => 'Üç', 'slug' => 'uc', 'featured_image_path' => 'question-image/keep.png']);
        $this->assertNull($other->featured_image_url);
        $other->delete();
        Storage::disk('public')->assertExists('question-image/keep.png');
    }
}
