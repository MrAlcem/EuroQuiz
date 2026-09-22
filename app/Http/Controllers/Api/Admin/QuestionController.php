<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Resources\QuestionAdminResource;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Full CRUD over quiz questions, restricted to admins by route middleware.
 */
class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return QuestionAdminResource::collection(Question::latest()->get());
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
