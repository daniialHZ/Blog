<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function sendSignupAuthCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
        ]);

        // Generate a random 6-digit auth code
        $authCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Store in password_reset_tokens (reuse for simplicity)
        PasswordResetToken::updateOrCreate(
            ['email' => $request->email],
            [
                'auth_code' => $authCode,
                'expires_at' => Carbon::now()->addMinutes(10), // Code valid for 10 minutes
            ]
        );

        return response()->json([
            'message' => 'Auth code generated successfully',
            'auth_code' => $authCode
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'auth_code' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Check if the auth code is valid
        $resetEntry = PasswordResetToken::where('email', $request->email)
            ->where('auth_code', $request->auth_code)
            ->where('expires_at', '>', now()) // Ensure it's not expired
            ->first();

        if (!$resetEntry) {
            return response()->json(['message' => 'Invalid or expired auth code'], 400);
        }

        // Create user
        $user = User::create([
            'email' => $request->email,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => Hash::make($request->password),
        ]);

        // Generate API token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Delete the auth code after successful signup
        $resetEntry->delete();

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid login credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Generate a random 6-digit auth code
        $authCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetToken::updateOrCreate(
            ['email' => $request->email],
            [
                'auth_code' => $authCode,
                'expires_at' => Carbon::now()->addMinutes(10), // Code valid for 10 minutes
            ]
        );

        return response()->json([
            'message' => 'Auth code generated successfully',
            'auth_code' => $authCode
        ]);
    }

    public function validateAuthCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'auth_code' => 'required|string',
            'new_password' => 'required|min:6',
        ]);

        $resetEntry = PasswordResetToken::where('email', $request->email)
            ->where('auth_code', $request->auth_code)
            ->where('expires_at', '>', now()) // Ensure the code is not expired
            ->first();

        if (!$resetEntry) {
            return response()->json(['message' => 'Invalid or expired auth code'], 400);
        }

        // Reset password
        $user = User::where('email', $request->email)->first();
        $user->update(['password' => bcrypt($request->new_password)]);

        $resetEntry->delete();

        return response()->json(['message' => 'Password reset successfully']);
    }

}
