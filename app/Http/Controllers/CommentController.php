<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use \Illuminate\Support\Facades\Gate;
use Illuminate\Routing\Controllers\Middleware;

class CommentController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Blog $blog)
    {
        return $blog->comments()->with(['user'])->latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Blog $blog)
    {
        $validated = $request->validate([
            'content' => 'required',
        ]);
        $user = $request->user();
        $comment = $blog->comments()->create([
            'content' => $validated['content'],
            'user_id' => $user->id,
        ]);

        // Load the user relationship for the created comment
        $comment->load('user');

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => $comment
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog,Comment $comment)
    {
        return response()->json([
            'comment'=>$comment->with(['user'])->first()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,Blog $blog, Comment $comment)
    {
            Gate::authorize('update', $comment);
            $validated = $request->validate([
                'content' => 'required',
            ]);
            $comment->update($validated);
            return response()->json([
                'message'=>'Comment updated successfully',
                'comment'=>$comment->with(['user'])->first()
            ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog, Comment $comment)
    {
        Gate::authorize('update', $comment);
        $comment->delete();
        return response()->json([
            'message'=>'Comment deleted successfully',
        ], 200);
    }
}
