<?php

namespace App\Models\Concerns;

use App\Models\Question;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TracksPublication
{
    public function initializeTracksPublication(): void
    {
        $this->mergeCasts(['published_at' => 'datetime', 'first_published_at' => 'datetime', 'publication_history_unknown' => 'boolean', 'content_updated_at' => 'datetime']);
    }

    public function isContentPublished(): bool
    {
        return $this instanceof Question
            ? ($this->status ?? 'Yayında') === 'Yayında'
            : (bool) ($this->is_published ?? true);
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function recordContentEdit(): void
    {
        $user = auth()->user();
        $isStaff = $user instanceof User && ($user->isAdministrator() || $user->isEditor());
        $this->last_edited_by = $isStaff ? $user->getKey() : null;
        $this->last_editor_name = $isStaff ? $user->name : null;
        if ($this->first_published_at) $this->content_updated_at = now();
    }

    public static function bootTracksPublication(): void
    {
        static::creating(function ($record) {
            $record->publication_history_unknown = false;
            $record->content_updated_at = null;
            $record->last_edited_by = null;
            $record->last_editor_name = null;
            $record->first_published_at = $record->isContentPublished() ? now() : null;
        });
        static::updating(function ($record) {
            $record->first_published_at = $record->getOriginal('first_published_at');
            $record->content_updated_at = $record->getOriginal('content_updated_at');
            $record->last_edited_by = $record->getOriginal('last_edited_by');
            $record->last_editor_name = $record->getOriginal('last_editor_name');
            $contentFields = array_diff($record->getFillable(), [
                'status', 'is_published', 'views_count', 'view_count', 'question_count',
                'created_by', 'creator_name',
            ]);
            // Form hydration can turn an empty optional field into null (or vice versa).
            // That does not change the content and must not replace the displayed publication date.
            $contentFields = array_filter($contentFields, fn ($field) => !(
                in_array($record->getOriginal($field), [null, ''], true)
                && in_array($record->getAttribute($field), [null, ''], true)
            ));
            $optionsChanged = false;
            if ($record instanceof Question) {
                $original = new Question;
                $original->setRawAttributes($record->getRawOriginal(), true);
                $optionsChanged = $original->options_list !== $record->options_list;
                $contentFields = array_diff($contentFields, ['options']);
            }
            if ($record->isDirty($contentFields) || $optionsChanged) {
                $record->recordContentEdit();
            }
            $field = $record instanceof Question || $record instanceof Page ? 'status' : 'is_published';
            $wasPublished = $record instanceof Question
                ? $record->getOriginal($field) === 'Yayında'
                : ($record instanceof Page ? $record->getOriginal($field) === 'published' : (bool) $record->getOriginal($field));
            if (!$record->first_published_at && !$wasPublished && $record->isContentPublished()) {
                $record->first_published_at = now();
                $record->publication_history_unknown = false;
            }
        });
    }
}
