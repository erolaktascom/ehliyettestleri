<?php

namespace App\Livewire\Admin\Concerns;

use App\Support\QuestionMedia;
use InvalidArgumentException;

trait ResolvesEditorVideo
{
    public function resolveEditorVideo(string $input): array
    {
        if (trim($input) === '' || mb_strlen($input) > 10000) {
            return ['error' => 'Video bağlantısı veya embed kodu girin (en fazla 10.000 karakter).'];
        }

        try {
            return ['html' => QuestionMedia::embed($input)];
        } catch (InvalidArgumentException $exception) {
            return ['error' => $exception->getMessage()];
        }
    }
}
