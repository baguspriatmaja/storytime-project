<?php

namespace App\Http\Controllers; 

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
        'identifier' => 'required',
        'password' => 'required',
    ]);

    if ($validator->fails()) {
    return response()->json([
        "data" => [
            "errors" => $validator->invalid()
            ]
        ], 422);
    }

    $user = User::where('email', $request->identifier)
                ->orWhere('username', $request->identifier)
                ->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'identifier' => ['The provided credentials are incorrect.'],
        ]);
    }

    $token = $user->createToken("tokenName")->plainTextToken;

    $expiresIn = now()->addHours(1)->toDateTimeString();

    return response()->json([
        "data" => [
            "message" => "Login Successful",
            "token" => $token,
            "expiresIn" => $expiresIn,
            "user" => $user,
        ]
    ]);
    
    }

    public function register(Request $request)
    {
    // Validasi input
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'username' => 'required|string|unique:users|max:255',
        'email' => 'required|string|email|unique:users|max:255',
        'password' => 'required|string|min:8',
        'confirm_password' => 'required|string|same:password',
    ]);

    // Jika validasi gagal
    if ($validator->fails()) {
        return response()->json([
            "success" => false,
            "message" => "Validation errors",
            "errors" => $validator->errors()
        ], 422);
    }

    try {
        // Buat user baru
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Buat token autentikasi
        $token = $user->createToken("tokenName")->plainTextToken;

        return response()->json([
            "success" => true,
            "message" => "Register successful",
            "data" => [
                "user" => $user,
                "token" => $token
            ]
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            "success" => false,
            "message" => "Server error",
            "error" => $e->getMessage()
        ], 500);
    }
}
 
    // Fungsi untuk logout
    public function logout(Request $request)
    {
        
        $request->user()->tokens()->delete();

        return response()->json([
            "message" => "Logged out successfully"
        ]);
    }

    public function updateProfile(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'about' => 'nullable|string|max:500',
        'old_password' => 'nullable|string|min:8',
        'new_password' => 'nullable|string|min:8|different:old_password',
        'confirm_new_password' => 'nullable|same:new_password',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422);
    }

    $user = auth()->user();

    try {
        if ($request->filled('name') || $request->filled('about')) {
            if ($request->filled('name')) {
                $user->name = $request->name;
            }
            if ($request->filled('about')) {
                $user->about = $request->about;
            }
        }

        if ($request->filled('old_password') && $request->filled('new_password')) {
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Old password is incorrect.',
                ], 400);
            }

            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user,
        ], 200);

        } catch (\Exception $e) {
            return response()->json([
            'success' => false,
            'message' => 'Server error.',
            'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'imageLink' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        try {
            if ($request->hasFile('imageLink')) {
                $imagePath = $request->file('imageLink')->store('profile_images', 'public');
                $user->imageLink = $imagePath;
                $user->save(); 
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile image updated successfully.',
                'data' => $user,
                'profile_url' => asset('storage/' . $user->imageLink),
            ], 
            200);
        } catch (\Exception $e) {
                return response()->json([
                'success' => false,
                'message' => 'Server error.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAuthUser(Request $request)
    {
        $user = $request->user();

        if ($user) {
            return response()->json([
                'success' => true,
                'message' => 'Authenticated user fetched successfully.',
                'data' => $user,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No authenticated user found.',
            ], 404);
        }
    }



}
