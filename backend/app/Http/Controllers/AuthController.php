<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Features\OfflineSync\Services\OfflineSyncEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'passwordHash' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return response()->json([
            'user' => UserResource::make($user),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->session()->regenerate();

        if ($request->boolean('remember_me')) {
            $request->session()->put('_remember_me', true);
            // Note: session lifetime is controlled via config/session.php (lifetime key).
            // Extended remember-me sessions should be configured at the infrastructure level.
        }

        return response()->json([
            'user' => UserResource::make($request->user()),
            'remember_me' => $request->boolean('remember_me'),
        ]);
    }

    public function logout(Request $request)
    {
        $userId = $request->user()?->id;

        if ($userId) {
            $tokens = PushNotificationDevice::where('user_id', $userId)->pluck('token')->all();
            PushNotificationDevice::where('user_id', $userId)->delete();

            // The device token rows are gone, so any offline operations they
            // had queued are orphaned too — purge them.
            (new OfflineSyncEngine())->purgeDeviceOperations($tokens);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles'));
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['email' => __($status)], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'passwordHash' => Hash::make($password)
                ]);
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['email' => [__($status)]], 400);
    }
}
