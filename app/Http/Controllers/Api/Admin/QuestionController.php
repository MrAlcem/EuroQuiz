<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Resources\QuestionAdminResource;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Full CRUD over quiz questions, restricted to admins by route middleware.
 */
class QuestionController extends Controller
{
    /**
     * Display a listing of the resource, optionally filtered by category
     * and/or country so admins can organize questions by either.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $questions = Question::query()
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('country'), fn ($query) => $query->where('country', $request->string('country')))
            ->latest()
            ->get();

        return QuestionAdminResource::collection($questions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $question = Question::create($request->validated());

        return (new QuestionAdminResource($question))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question): QuestionAdminResource
    {
        return new QuestionAdminResource($question);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question): QuestionAdminResource
    {
        $question->update($request->validated());

        return new QuestionAdminResource($question);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question): JsonResponse
    {
        $question->delete();

        return response()->json(status: 204);
    }
}
