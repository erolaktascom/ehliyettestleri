<?php
namespace App\Http\Middleware;

use App\Models\ExamAttempt;
use Closure;
use Illuminate\Http\Request;

class LeaveTestOnNavigation
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->isMethod('GET') && !$request->expectsJson()
            && !$request->is('admin', 'admin/*', 'livewire/*')
            && !$request->routeIs('exams.play', 'exams.take', 'exams.result', 'topic-tests.play', 'topic-tests.take', 'animated-questions.play')
            && $response->getStatusCode() === 200
            && str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            if ($request->user()) {
                ExamAttempt::where('user_id', $request->user()->id)->where('status', 'in_progress')
                    ->whereHas('exam', fn ($q) => $q->where('slug', '!=', 'yanlis-sorular-tekrar-testi'))
                    ->update(['status' => 'abandoned']);
            }
        }
        return $response;
    }
}
