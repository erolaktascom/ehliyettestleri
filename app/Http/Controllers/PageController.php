<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        Page::withoutTimestamps(fn () => $page->increment('view_count'));

        return view('pages.page', compact('page'));
    }
}
