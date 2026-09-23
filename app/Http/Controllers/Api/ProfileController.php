<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResultResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exposes the authenticated user's profile and quiz history.
 */
class ProfileController extends Controller
{
    /**
     * Show the authenticated user's profile: best score and quiz history.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $results = $user->results()->latest()->get();

        return response()->json([
            'username' => $user->name,
            'email' => $user->email,
            'best_score' => $results->max('score') ?? 0,
            'total_score' => $user->total_score,
            'level' => $user->level()->value,
            'history' => ResultResource::collection($results),
        ]);
    }
}
