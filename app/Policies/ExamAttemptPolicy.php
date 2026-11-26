<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;

class ExamAttemptPolicy
{
    public function view(?User $user, ExamAttempt $attempt): bool
    {
        if ($attempt->status === 'in_progress'
            && $attempt->exam?->slug === 'yanlis-sorular-tekrar-testi'
            && (!$user || !$user->isPremium())) {
            return false;
        }

        return $user !== null && $attempt->user_id !== null && (int) $attempt->user_id === (int) $user->id;
    }
}
