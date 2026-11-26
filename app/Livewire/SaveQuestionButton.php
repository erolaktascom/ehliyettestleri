<?php

namespace App\Livewire;

use App\Models\SavedQuestion;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SaveQuestionButton extends Component
{
    public int $questionId;
    public bool $isSaved = false;

    public function mount(int $questionId)
    {
        $this->questionId = $questionId;
        if (Auth::check()) {
            $this->isSaved = SavedQuestion::where('user_id', Auth::id())
                ->where('question_id', $this->questionId)
                ->exists();
        }
    }

    public function toggleSave()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();
        $existing = SavedQuestion::where('user_id', $userId)
            ->where('question_id', $this->questionId)
            ->first();

        if ($existing) {
            $existing->delete();
            $this->isSaved = false;
        } else {
            SavedQuestion::create([
                'user_id' => $userId,
                'question_id' => $this->questionId,
            ]);
            $this->isSaved = true;
        }
    }

    public function render()
    {
        return view('livewire.save-question-button');
    }
}
