<?php

namespace App\Livewire\Admin;

use App\Models\QuestionImport;
use Livewire\Component;
use Livewire\WithPagination;

class QuestionImports extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public function delete(int $id)
    {
        QuestionImport::findOrFail($id)->delete();
        session()->flash('success', 'Aktarım kaydı silindi.');
    }

    public function render()
    {
        $query = QuestionImport::with('user');

        if ($this->search) {
            $query->where('file_name', 'like', '%' . $this->search . '%');
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $imports = $query->latest()->paginate(15);

        return view('livewire.admin.question-imports', [
            'imports' => $imports,
        ])->layout('layouts.admin', ['title' => 'Toplu Soru Aktarımı']);
    }
}
