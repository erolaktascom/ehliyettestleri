<?php
namespace Tests\Feature;

use App\Livewire\Admin\{ContentCategoryForm, PremiumPlanForm, QuestionTopicForm, TopicForm, TrafficSignForm, TrafficSignCategoryForm};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderingValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_order_fields_reject_negative_input_before_database_write(): void
    {
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        foreach ([ContentCategoryForm::class, PremiumPlanForm::class, QuestionTopicForm::class,
            TopicForm::class, TrafficSignForm::class, TrafficSignCategoryForm::class] as $form) {
            Livewire::test($form)->set('order', -1)->call('save')->assertHasErrors(['order'=>'min']);
        }
    }
}
