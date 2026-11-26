<?php

namespace App\Http\Controllers;

use App\Services\ContactMessageSubmission;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function show()
    {
        return view('pages.contact');
    }

    public function submit(Request $request, ContactMessageSubmission $submission)
    {
        $submission->submit($request->only(['name', 'email', 'phone', 'subject', 'message', 'website']));

        return back()->with('success', 'Mesajınız başarıyla bize ulaştı. En kısa sürede dönüş yapacağız.');
    }
}
