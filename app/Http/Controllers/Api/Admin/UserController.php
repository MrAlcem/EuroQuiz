<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Read-only overview of registered users, their scores and their quiz
 * activity, for the admin panel. Restricted to admins by route middleware.
 */
class UserController extends Controller
{
    /**
     * List every user with their total score, quiz count and last activity.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->withCount('results')
            ->withMax('results', 'created_at')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($userQuery) use ($search): void {
                    $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->orderByDesc('total_score')
            ->get();

        return AdminUserResource::collection($users);
    }

    public function show(User $user): AdminUserResource
    {
        $user->loadCount('results')->loadMax('results', 'created_at');

        return new AdminUserResource($user);
    }

    public function update(Request $request, User $user): AdminUserResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['sometimes', 'required', Rule::enum(UserRole::class)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'reset_mfa' => ['sometimes', 'boolean'],
        ]);

        $attributes = collect($validated)->only(['name', 'email', 'role'])->all();

        if (array_key_exists('password', $validated) && filled($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        if (($validated['reset_mfa'] ?? false) === true) {
            $attributes['two_factor_enabled'] = false;
            $attributes['two_factor_secret'] = null;
        }

        $user->update($attributes);
        $user->loadCount('results')->loadMax('results', 'created_at');

        return new AdminUserResource($user);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'message' => 'You cannot delete your own administrator account.',
            ], 422);
        }

        $user->delete();

        return response()->json(status: 204);
    }
}
