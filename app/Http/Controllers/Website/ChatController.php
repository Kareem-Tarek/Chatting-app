<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User, Message};
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ChatController extends Controller
{
    public function index()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return view('website.chat.index', compact('users'));
    }

    public function fetchMessages(Request $request)
    {
        $messages = Message::where(function ($query) use ($request) {
                $query->where('sender_id', Auth::id())
                    ->where('receiver_id', $request->receiver_id);
            })
            ->orWhere(function ($query) use ($request) {
                $query->where('receiver_id', Auth::id())
                    ->where('sender_id', $request->receiver_id);
            })
            ->with(['sender']) // Eager load the sender relationship
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message'     => 'required|string|max:255'
        ]);

        $message = Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
        ]);

        return response()->json($message);
    }

    public function markAsSeen(Request $request)
    {
        Message::where('receiver_id', Auth::id())
            ->where('sender_id', $request->receiver_id)
            ->where('seen', false)
            ->update(['seen' => true, 'seen_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function fetchUnseenCounts()
    {
        $users = User::where('id', '!=', Auth::id())->get();

        $unseenCounts = $users->map(function ($user) {
            $unseen = Message::where('sender_id', $user->id)
                            ->where('receiver_id', Auth::id())
                            ->where('seen', false)
                            ->count();

            return [
                'id' => $user->id,
                'unseen' => $unseen
            ];
        });

        return response()->json($unseenCounts);
    }

    public function update(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        if ($message->sender_id == Auth::id() && $message->created_at->diffInMinutes(now()) <= 15) {
            $message->update(['message' => $request->message]);
        }
        return response()->json($message);
    }

    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        if ($message->sender_id == Auth::id() && $message->created_at->diffInHours(now()) <= 60) {
            $message->delete();
        }
        return response()->json(['success' => true]);
    }
}
