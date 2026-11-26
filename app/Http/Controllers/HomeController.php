<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\TopicCategory;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $featuredExam = Exam::where('is_published', true)->withCount(['questions as published_questions_count' => fn ($query) => $query->where('questions.status', 'Yayında')])->orderBy('order')->first();
        $topicCategories = TopicCategory::where('is_active', true)->orderBy('order')->get();

        return view('pages.home', compact('featuredExam', 'topicCategories'));
    }

}
