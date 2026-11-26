<?php

namespace App\Livewire\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;

trait EditsPublicationDate
{
    public ?string $published_at = null;

    protected function loadPublicationDate(?Model $record = null): void
    {
        $this->published_at = $record
            ? $record->published_at?->format('Y-m-d\TH:i')
            : now()->format('Y-m-d\TH:i');
    }

    protected function publicationDateForSave(Model $record): mixed
    {
        return $record->exists && $record->published_at?->format('Y-m-d\TH:i') === $this->published_at
            ? $record->published_at
            : $this->published_at;
    }
}
