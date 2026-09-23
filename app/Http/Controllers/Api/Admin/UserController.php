<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only overview of registered users, their scores and their quiz
 * activity, for the admin panel. Restricted to admins by route middleware.
 */
class UserController extends Controller
{
    /**
     * List every user with their total score, quiz count and last activity.
     */
    public function index(): AnonymousResourceCollection
    {
        $users = User::query()
            ->withCount('results')
            ->withMax('results', 'created_at')
            ->orderByDesc('total_score')
            ->get();

        return AdminUserResource::collection($users);
    }
}
