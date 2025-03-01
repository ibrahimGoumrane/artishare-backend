<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Like;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use PhpParser\Node\Scalar\String_;

class LikeController extends Controller  implements HasMiddleware
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
        return $blog->likes()->with(['user'])->latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request , Blog $blog)
    {
        $user = $request->user();
        $message = $this->toggleLike($user , $blog);
        return response()->json([
            'message'=>$message,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Like $like)
    {
        return response()->json([
            'like'=>$like
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Like $like)
    {
            //
    }
    public function toggleLike(User $user , Blog $blog) :string
    {

        // Check if the user has already liked the blog
        $existingLike = $blog->likes()->where('user_id', $user->id)->first();

        if ($existingLike) {
            // If the like exists, remove it
            $existingLike->delete();

            return 'Like removed successfully.';
        } else {
            // If the like does not exist, add it
            $blog->likes()->create([
                'user_id' => $user->id, // Assign the user's ID
            ]);

            return 'Like added successfully.';
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Like $like)
    {
        //
    }
}
