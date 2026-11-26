<?php

namespace App\Http\Controllers;

use App\Models\NotificationDelivery;
use Illuminate\Http\Request;

class MemberNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = NotificationDelivery::where('user_id', $request->user()->id)
            ->where('channel', 'in_app')->where('status', 'sent')->latest('sent_at')->paginate(20);
        return view('pages.notifications', compact('notifications'));
    }

    public function read(Request $request, int $id)
    {
        $notification = NotificationDelivery::where('user_id', $request->user()->id)
            ->where('channel', 'in_app')->where('status', 'sent')->findOrFail($id);
        if (!$notification->read_at) $notification->update(['read_at' => now()]);
        return back();
    }
}
