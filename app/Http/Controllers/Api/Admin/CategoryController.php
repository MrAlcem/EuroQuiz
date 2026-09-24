<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminCategoryResource;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AdminCategoryResource::collection(
            Category::query()->withCount('questions')->orderBy('name')->get(),
        );
    }

    public function store(Request $request): AdminCategoryResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        return new AdminCategoryResource(Category::create($validated));
    }

    public function update(Request $request, Category $category): AdminCategoryResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category)],
        ]);

        DB::transaction(function () use ($category, $validated): void {
            $oldName = $category->name;
            $category->update(['name' => $validated['name']]);
            Question::where('category', $oldName)->update(['category' => $validated['name']]);
        });

        return new AdminCategoryResource($category->fresh()->loadCount('questions'));
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $questionCount = Question::where('category', $category->name)->count();

        $validated = $request->validate([
            'replacement_category' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('categories', 'name')->where(fn ($query) => $query->where('id', '!=', $category->id)),
            ],
        ]);

        if ($questionCount > 0 && empty($validated['replacement_category'])) {
            return response()->json([
                'message' => "Choose another category before deleting this category's questions.",
            ], 422);
        }

        DB::transaction(function () use ($category, $validated): void {
            if (! empty($validated['replacement_category'])) {
                Question::where('category', $category->name)
                    ->update(['category' => $validated['replacement_category']]);
            }

            $category->delete();
        });

        return response()->json(status: 204);
    }
}
