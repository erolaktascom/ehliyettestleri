<?php

namespace App\Livewire\Admin;

use App\Models\QuestionImport;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Soru Aktarım Formu'])]
class QuestionImportForm extends Component
{
    public ?int $importId = null;
    public string $file_name = '';
    public string $status = 'Tamamlandı';
    public int $total = 0;
    public int $ready = 0;
    public int $failed = 0;
    public int $imported = 0;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $item = QuestionImport::findOrFail($id);
            $this->importId = $item->id;
            $this->file_name = $item->file_name;
            $this->status = $item->status;
            $this->total = (int) $item->total;
            $this->ready = (int) $item->ready;
            $this->failed = (int) $item->failed;
            $this->imported = (int) $item->imported;
        } else {
            $this->status = 'Tamamlandı';
        }
    }

    public function rules(): array
    {
        return [
            'file_name' => 'required|string|max:255',
            'status' => 'required|string',
            'total' => 'required|integer|min:0',
            'ready' => 'required|integer|min:0',
            'failed' => 'required|integer|min:0',
            'imported' => 'required|integer|min:0',
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        if ($this->importId) {
            QuestionImport::findOrFail($this->importId)->update($validated);
            session()->flash('success', 'Soru aktarım kaydı güncellendi.');
        } else {
            $validated['user_id'] = auth()->id();
            QuestionImport::create($validated);
            session()->flash('success', 'Soru aktarım kaydı oluşturuldu.');
        }

        if (!$this->importId) {
            return redirect()->route('admin.question-imports');
        }
    }

    public function render()
    {
        return view('livewire.admin.question-import-form');
    }
}
