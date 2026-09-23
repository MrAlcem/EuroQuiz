<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ])->refresh();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'token' => $token,
            'user' => $user,
            'mfa_required' => false,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user->two_factor_enabled) {
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user,
                'mfa_required' => false,
            ]);
        }

        $challenge = bin2hex(random_bytes(32));
        Cache::put("mfa_challenge:$challenge", $user->id, now()->addMinutes(5));

        return response()->json([
            'message' => 'MFA required.',
            'mfa_required' => true,
            'challenge_token' => $challenge,
        ]);
    }

    public function verifyMfa(Request $request)
    {
        $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = Cache::get('mfa_challenge:'.$request->challenge_token);

        if (! $userId) {
            return response()->json([
                'message' => 'Challenge expired or invalid.',
            ], 401);
        }

        $user = User::find($userId);

        if (! $user || ! $user->two_factor_secret) {
            return response()->json([
                'message' => 'MFA not configured for this user.',
            ], 422);
        }

        $google2fa = new Google2FA;
        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code, 2);

        if (! $valid) {
            return response()->json([
                'message' => 'Invalid MFA code.',
            ], 422);
        }

        Cache::forget('mfa_challenge:'.$request->challenge_token);
        Auth::login($user);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful with MFA.',
            'token' => $token,
            'user' => $user,
            'mfa_required' => false,
        ]);
    }
}
