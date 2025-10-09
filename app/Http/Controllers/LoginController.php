<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();

            $user->refresh();

            return response()->json([
                'message'        => 'Login successful',
                'user'           => $user,
                'last_login_at'  => $user->last_login_at,
            ], 200);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request)
    {
        // If the request has an authenticated user, revoke their tokens (Sanctum)
        $user = $request->user();
        if ($user) {
            // Delete the current access token if present
            try {
                if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                    $user->currentAccessToken()->delete();
                }
            } catch (\Throwable $e) {
                // ignore token deletion errors
            }

            // Also delete all other tokens to fully revoke API access
            try {
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            } catch (\Throwable $e) {
                // ignore token deletion errors
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out'], 200);
    }
}
