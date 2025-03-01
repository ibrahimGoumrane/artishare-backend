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
        try {
            $fields = $request->validate([
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:4096', // Validate profile image (optional)
                'email' => 'required|email|unique:users',
                'password' => 'required|confirmed', // Ensure passwords match
            ]);

            // Default profile photo path
            $profilePhotoPath = '/storage/uploads/profile/default.png'; // Ensure this exists in storage/app/uploads/profile/

            // Check if 'profile_image' file is uploaded
            if ($request->hasFile('profile_image')) {
                $profilePhoto = $request->file('profile_image'); // Get uploaded file

                // Save the file to 'uploads/profile/' with a unique name
                $profilePhotoName = time() . '_' . $profilePhoto->getClientOriginalName(); // Unique file name
                $profilePhotoPath = $profilePhoto->storeAs('uploads/profile', $profilePhotoName, 'public'); // Efficient storage
            }

            // Create user with hashed password and the profile image
            $user = User::create([
                'first_name' => $fields['first_name'],
                'last_name' => $fields['last_name'],
                'email' => $fields['email'],
                'profile_image' => '/storage/' . $profilePhotoPath, // Path to profile image
                'password' => Hash::make($fields['password']), // Hash the password before storing
            ]);

            // Generate a token for the user
            $token = $user->createToken($request->first_name);

            // Return response with the user and token
            return response()->json([
                'user' => $user->load(['blogs', 'comments', 'likes']),
                'token' => $token->plainTextToken,
            ], 201);
        } catch (Exception $e) {
            // Log the error and return a generic server error response
            Log::error('Error in register method: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred during registration. Please try again later.',
            ], 500);
        }
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
