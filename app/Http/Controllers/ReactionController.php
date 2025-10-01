<?php

namespace App\Http\Controllers;

use App\Models\Reaction;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReactionController extends Controller
{
    public function store(Request $request, $postId)
    {
        $request->validate([
            'type' => 'required|in:like,dislike,heart',
        ]);

        $post = Post::find($postId);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $existingReaction = Reaction::where('user_id', Auth::id())
            ->where('post_id', $postId)
            ->first();

        if ($existingReaction) {
            $existingReaction->type = $request->type;
            $existingReaction->save();
            return response()->json(['message' => 'Reaction updated.']);
        }

        Reaction::create([
            'type' => $request->type,
            'user_id' => Auth::id(),
            'post_id' => $postId,
        ]);

        return response()->json(['message' => 'Reaction added.'], 201);
    }

    public function getReactions($postId)
    {
        $reactions = Reaction::where('post_id', $postId)->get();

        $reactionCounts = [
            'like' => $reactions->where('type', 'like')->count(),
            'dislike' => $reactions->where('type', 'dislike')->count(),
            'heart' => $reactions->where('type', 'heart')->count(),
        ];

        return response()->json($reactionCounts);
    }
}
