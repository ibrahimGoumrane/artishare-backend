<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller implements HasMiddleware
{

    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::latest()
                   ->get()
                   ->makeHidden(['blogs', 'comments', 'likes']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load([
                'blogs.comments.user',
                'blogs.likes.user',
                'blogs.user',
                'blogs.categories',
                'likes.blog.user', // Include the creator of liked blogs
                'likes.blog.comments' ,// Include the categories of liked blogs
                'likes.blog.likes' ,
                'comments.blog.user', // Include the creator of commented blogs
                'comments.blog.comments.user' ,// Include the categories of commented blogs
                'comments.blog.likes.user' ,
                'comments.user'
            ]),
        ]);
    }

        /**
     * Search for users by first name, last name, or email.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');

        $users = User::where('first_name', 'LIKE', "%{$query}%")
                     ->orWhere('last_name', 'LIKE', "%{$query}%")
                     ->orWhere('email', 'LIKE', "%{$query}%")
                     ->select('id', 'first_name', 'last_name', 'email', 'profile_image', 'role', 'account_locked')
                     ->latest()
                     ->get();

        return response()->json($users);
    }

    /**
     * Toggle the account_locked status of a user.
     */
    public function lock(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->account_locked = !$user->account_locked;
        $user->save();

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Update the specified image of the user
     */
    public function uploadProfileImage(Request $request, User $user)
    {
        // Validate the profile image
        $request->validate([
            'profile_image' => 'nullable|sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:4096', // Profile image validation
        ]);

        if ($request->hasFile('profile_image')) {
            $profileImage = $request->file('profile_image');

            // Generate a new filename for the uploaded file
            $profileImageName = 'uploads/profile/' . time() . '_' . $profileImage->getClientOriginalName();

            // Save the new profile image using the storage system
            Storage::disk('public')->put($profileImageName, file_get_contents($profileImage));

            // Optionally, delete the old profile image if it's not a default one
            if ($user->profile_image && $user->profile_image !== '/storage/uploads/profile/default.png') {
                Storage::disk('public')->delete($user->profile_image);
            }

            // Update the user's profile image path
            $user->update([
                'profile_image' => '/storage/' . $profileImageName,
            ]);

            return response()->json([
                'message' => 'Profile image updated successfully.',
                'user' => $user->load(['blogs', 'comments.blog', 'likes.blog']),
            ], 200);
        }

        return response()->json([
            'message' => 'No profile image found in the request.',
        ], 400);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Authorize the user for updating the resource
        Gate::authorize('update', $user);

        // Validate the request data
        $validated = $request->validate([
            'first_name' => 'nullable|max:255',
            'last_name' => 'nullable|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id, // Ignore unique check for the current user
        ]);

        // Update the user's information
        $user->update($validated);

        return response()->json([
            'message' => 'User details updated successfully.',
            'user' => $user,
        ], 200);
    }    /**
     * Remove the specified resource from storage.
     */

    /**
     * Update the user password
     *
     */
    public function updatePassword(Request $request, User $user)
    {
        // Authorize the user for password updates
        Gate::authorize('update', $user);

        // Validate the password input
        $validated = $request->validate([
            'current_password' => 'required', // Require the user's current password for verification
            'new_password' => 'required|string|min:8', // Require a new password with confirmation
        ]);

        // Verify the current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'The provided current password is incorrect.',
            ], 400);
        }

        // Update the user's password
        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
            'user' => $user->load(['blogs', 'comments.blog', 'likes.blog']),
        ], 200);
    }
    public function destroy(User $user)
    {
        Gate::authorize('update', $user);
            $user->delete();
            return response()->json([
                'message' => 'User deleted successfully.',
            ], 200);
    }
}
