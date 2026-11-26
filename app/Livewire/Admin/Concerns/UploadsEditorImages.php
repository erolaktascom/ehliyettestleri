<?php

namespace App\Livewire\Admin\Concerns;

use Illuminate\Support\Facades\Validator;
use Livewire\WithFileUploads;

trait UploadsEditorImages
{
    use WithFileUploads;

    public array $editorImageUploads = [];

    public function storeEditorImage(string $field): array
    {
        abort_unless(in_array($field, ['question_text', 'explanation', 'content'], true)
            && property_exists($this, $field), 422);

        $upload = $this->editorImageUploads[$field] ?? null;
        $validator = Validator::make(['image' => $upload], [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096',
        ], [
            'image.required' => 'Yüklemek için bir görsel seçin.',
            'image.image' => 'Geçerli bir görsel dosyası seçin.',
            'image.mimes' => 'JPG, PNG veya WEBP görseli seçin.',
            'image.max' => 'Görsel en fazla 2 MB olabilir.',
            'image.dimensions' => 'Görsel boyutları en fazla 4096 × 4096 piksel olabilir.',
        ]);

        if ($validator->fails()) {
            unset($this->editorImageUploads[$field]);
            return ['error' => $validator->errors()->first('image')];
        }

        try {
            $path = $upload->store('question-image', 'public');
            if (!$path) return ['error' => 'Görsel kaydedilemedi. Tekrar deneyin.'];
            return ['url' => asset('storage/'.$path)];
        } catch (\Throwable $exception) {
            report($exception);
            return ['error' => 'Görsel yüklenemedi. Tekrar deneyin.'];
        } finally {
            unset($this->editorImageUploads[$field]);
        }
    }
}
