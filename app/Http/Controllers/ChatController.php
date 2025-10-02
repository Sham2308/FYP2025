<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    // GET /chat/messages?room=borrow&after=123
    public function index(Request $request)
    {
        $room  = $request->query('room', 'borrow');
        $after = (int) $request->query('after', 0);

        $messages = ChatMessage::with('user:id,name')
            ->where('room', $room)
            ->when($after, fn ($q) => $q->where('id', '>', $after))
            ->orderBy('id')
            ->limit(100)
            ->get();

        return response()->json($messages);
    }

    // POST /chat/messages  { body: "...", room?: "borrow", guest_name?: "Ali" }
    public function store(Request $request)
    {
        $data = $request->validate([
            'body'       => ['required', 'string', 'max:2000'],
            'room'       => ['nullable', 'string', 'max:50'],
            'guest_name' => ['nullable', 'string', 'max:80'],
        ]);

        $room    = $data['room'] ?? 'borrow';
        $userId  = auth()->id(); // may be null for guests
        $gName   = $userId ? null : ($data['guest_name'] ?? ('Guest-' . substr($request->session()->getId(), -4)));

        // 1) Save the user's/guest's message
        $userMsg = ChatMessage::create([
            'user_id'    => $userId,            // null for guests
            'guest_name' => $gName,             // set for guests
            'room'       => $room,
            'body'       => trim($data['body']),
        ])->load('user:id,name');

        // 2) Ask the AI to reply (best-effort; won't crash the request)
        try {
            $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');

            if ($apiKey) {
                $systemPrompt = "You are TapNBorrow Assistant. Answer user questions about borrowing items, "
                              . "inventory status, rules, and general help. Keep answers short, friendly, and clear. "
                              . "If asked about live database contents, say you can’t see it from here.";

                $resp = Http::withToken($apiKey)
                    ->timeout(20)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model'       => 'gpt-4o-mini',
                        'temperature' => 0.3,
                        'messages'    => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user',   'content' => $data['body']],
                        ],
                    ])->json();

                $aiText = trim((string) data_get($resp, 'choices.0.message.content', ''));

                ChatMessage::create([
                    'user_id'    => null,
                    'guest_name' => $aiText !== '' ? 'Assistant' : 'Assistant',
                    'room'       => $room,
                    'body'       => $aiText !== '' ? $aiText : "Sorry, I'm having trouble replying right now.",
                ]);
            } else {
                // No API key configured
                ChatMessage::create([
                    'user_id'    => null,
                    'guest_name' => 'Assistant',
                    'room'       => $room,
                    'body'       => 'AI is not configured yet. Please set OPENAI_API_KEY in .env.',
                ]);
            }
        } catch (\Throwable $e) {
            // Log if you like: \Log::error('AI error: '.$e->getMessage());
            ChatMessage::create([
                'user_id'    => null,
                'guest_name' => 'Assistant',
                'room'       => $room,
                'body'       => 'Sorry, something went wrong while generating a reply.',
            ]);
        }

        // Return the user's message; your poller will pick up the AI reply on next fetch.
        return response()->json($userMsg, 201);
    }
}
