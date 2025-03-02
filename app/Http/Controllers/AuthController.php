<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate input fields
        $fields = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|confirmed|min:8',
        ]);
    
        // Set default profile image path (relative path)
        $profilePhotoPath = '/storage/uploads/profile/profile1.jpeg';
    
        // Handle profile image upload to DigitalOcean Spaces
        if ($request->hasFile('profile_image')) {
            $profilePhoto = $request->file('profile_image');
            $profilePhotoName = time() . '_' . $profilePhoto->getClientOriginalName();
    
            // Store image in DigitalOcean Spaces
            $path = $profilePhoto->storeAs('storage/uploads/profile', $profilePhotoName, 'digitalocean');
    
            $profilePhotoPath = Storage::disk('digitalocean')->url($path);; // Store only the relative path
    
            // Log to console (this will appear in Laravel logs)
            info("Profile image uploaded: " . $profilePhotoPath);
        }
    
        // Create the user
        $user = User::create([
            'first_name' => $fields['first_name'],
            'last_name' => $fields['last_name'],
            'email' => $fields['email'],
            'profile_image' => $profilePhotoPath, // Store relative path in DB
            'password' => Hash::make($fields['password']),
        ]);
    
        // Generate API token
        $token = $user->createToken($user->first_name)->plainTextToken;
    
        return response()->json([
            'user' => $user->load(['blogs', 'comments', 'likes']),
            'token' => $token,
        ], 201);
    }
    

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users',
                'password' => 'required'
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.']
                    ]
                ], 401);
            }

            if ($user->account_locked) {
                return response()->json([
                    'message' => "Your account is locked. Please contact support."
                ], 423); // 423 Locked
            }

            $token = $user->createToken($user->first_name);

            return response()->json([
                'user' => $user->load(['blogs', 'comments', 'likes']),
                'token' => $token->plainTextToken
            ], 200);
        } catch (Exception $e) {
            // Log the error and return a generic server error response
            Log::error('Error in login method: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred during login. Please try again later.',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            return response()->json([
                'message' => 'You are logged out.',
            ], 200);
        } catch (Exception $e) {
            // Log the error and return a generic server error response
            Log::error('Error in logout method: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred during logout. Please try again later.',
            ], 500);
        }
    }
}
