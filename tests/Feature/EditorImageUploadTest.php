<?php

namespace Tests\Feature;

use App\Livewire\Admin\{QuestionForm, BlogPostForm, PageForm, TopicForm};
use App\Models\{Question, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EditorImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_all_editors_store_images_in_question_image_with_unique_names(): void
    {
        foreach ([QuestionForm::class, BlogPostForm::class, PageForm::class, TopicForm::class] as $component) {
            $field = $component === QuestionForm::class ? 'question_text' : 'content';
            $before = Storage::disk('public')->allFiles();
            $form = Livewire::test($component)->set($field, '<p>Korunan içerik</p>')
                ->set('editorImageUploads.'.$field, UploadedFile::fake()->image('same-name.png', 80, 60))
                ->call('storeEditorImage', $field)->assertHasNoErrors()->assertNoRedirect()
                ->assertSet($field, '<p>Korunan içerik</p>')->assertSet('editorImageUploads', []);
            $created = array_values(array_diff(Storage::disk('public')->allFiles(), $before));
            $this->assertCount(1, $created);
            $this->assertStringStartsWith('question-image/', $created[0]);
            $this->assertStringEndsWith('.png', $created[0]);
            $this->assertNotSame('question-image/same-name.png', $created[0]);
            $form->assertReturned(['url' => asset('storage/'.$created[0])]);
        }
        $this->assertCount(4, Storage::disk('public')->allFiles('question-image'));
    }

    public function test_invalid_files_are_rejected_without_permanent_uploads(): void
    {
        $files = [
            UploadedFile::fake()->createWithContent('script.php', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('fake.png', 'not an image'),
            UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            UploadedFile::fake()->image('large.jpg')->size(2049),
            UploadedFile::fake()->image('wide.png', 4097, 10),
        ];
        foreach ($files as $file) {
            $form = Livewire::test(QuestionForm::class)->set('editorImageUploads.question_text', $file)
                ->call('storeEditorImage', 'question_text')->assertSet('editorImageUploads', []);
            $this->assertArrayHasKey('error', $form->effects['returns'][0]);
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
        Livewire::test(QuestionForm::class)->call('storeEditorImage', 'question_text')
            ->assertReturned(['error' => 'Yüklemek için bir görsel seçin.']);
    }

    public function test_question_image_survives_save_reopen_and_other_editor_upload_is_preserved(): void
    {
        $question = Question::create(['question_text' => '<p>Soru?</p>', 'options' => [['key' => 'A', 'text' => 'Evet'], ['key' => 'B', 'text' => 'Hayır']], 'correct_option' => 'A', 'status' => 'Taslak']);
        $form = Livewire::test(QuestionForm::class, ['id' => $question->id])
            ->set('editorImageUploads.explanation', UploadedFile::fake()->image('explanation.jpg'))
            ->set('editorImageUploads.question_text', UploadedFile::fake()->image('question.png'))
            ->call('storeEditorImage', 'question_text');
        $this->assertArrayHasKey('explanation', $form->get('editorImageUploads'));
        $url = $form->effects['returns'][0]['url'];
        $html = '<p>Soru?<img src="'.$url.'"></p>';
        $form->set('question_text', $html)->call('save')->assertHasNoErrors()->assertNoRedirect();
        Livewire::test(QuestionForm::class, ['id' => $question->id])->assertSet('question_text', $html);
        $form->call('storeEditorImage', 'explanation')->assertHasNoErrors();
        $this->assertCount(2, Storage::disk('public')->allFiles('question-image'));
        $this->assertSame($html, $question->fresh()->question_text);
    }
}
