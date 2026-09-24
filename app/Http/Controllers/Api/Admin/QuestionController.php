<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Resources\QuestionAdminResource;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Full CRUD over quiz questions, restricted to admins by route middleware.
 */
class QuestionController extends Controller
{
    /**
     * Return the distinct country codes currently used by quiz questions.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'data' => [
                'countries' => Question::query()
                    ->whereNotNull('country')
                    ->where('country', '<>', '')
                    ->distinct()
                    ->orderBy('country')
                    ->pluck('country')
                    ->values(),
            ],
        ]);
    }

    /**
     * Import up to 500 questions from a CSV file. The import is all-or-nothing.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            return response()->json(['message' => 'The uploaded CSV could not be read.'], 422);
        }

        $requiredColumns = [
            'question_text', 'option_a', 'option_b', 'option_c', 'option_d',
            'correct_option', 'category', 'country', 'difficulty',
        ];
        $allowedColumns = [...$requiredColumns, 'time_limit_seconds'];
        $header = fgetcsv($stream, separator: ',', enclosure: '"', escape: '');

        if ($header === false) {
            fclose($stream);

            return response()->json(['message' => 'The CSV file is empty.'], 422);
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(static fn ($column): string => trim((string) $column), $header);
        $missingColumns = array_values(array_diff($requiredColumns, $header));

        if ($missingColumns !== [] || count($header) !== count(array_unique($header))) {
            fclose($stream);

            return response()->json([
                'message' => 'The CSV header is invalid. Include each required column once: '.implode(', ', $requiredColumns).'.',
                'missing_columns' => $missingColumns,
            ], 422);
        }

        $rows = [];
        $errors = [];
        $recordNumber = 1;
        $questionCount = 0;

        while (($values = fgetcsv($stream, separator: ',', enclosure: '"', escape: '')) !== false) {
            $recordNumber++;

            if (count($values) === 1 && trim((string) $values[0]) === '') {
                continue;
            }

            $questionCount++;
            if ($questionCount > 500) {
                $errors[] = ['row' => $recordNumber, 'errors' => ['file' => ['A CSV import can contain at most 500 questions.']]];
                break;
            }

            if (count($values) !== count($header)) {
                $errors[] = ['row' => $recordNumber, 'errors' => ['row' => ['The number of values does not match the CSV header.']]];

                continue;
            }

            $row = array_combine($header, $values);
            $question = array_intersect_key($row, array_flip($allowedColumns));
            if (isset($question['time_limit_seconds']) && trim((string) $question['time_limit_seconds']) === '') {
                unset($question['time_limit_seconds']);
            }

            $validator = Validator::make($question, [
                'question_text' => ['required', 'string'],
                'option_a' => ['required', 'string', 'max:255'],
                'option_b' => ['required', 'string', 'max:255'],
                'option_c' => ['required', 'string', 'max:255'],
                'option_d' => ['required', 'string', 'max:255'],
                'correct_option' => ['required', 'string', 'in:A,B,C,D'],
                'category' => ['required', 'string', 'max:255'],
                'country' => ['required', 'string', 'max:255'],
                'difficulty' => ['required', 'string', 'in:easy,medium,hard'],
                'time_limit_seconds' => ['sometimes', 'integer', 'min:5', 'max:120'],
            ]);

            if ($validator->fails()) {
                $errors[] = ['row' => $recordNumber, 'errors' => $validator->errors()->messages()];

                continue;
            }

            $rows[] = $validator->validated();
        }

        fclose($stream);

        if ($recordNumber === 1) {
            return response()->json(['message' => 'The CSV contains no question rows.'], 422);
        }

        if ($errors !== []) {
            return response()->json([
                'message' => 'No questions were imported. Fix the CSV errors and try again.',
                'errors' => $errors,
            ], 422);
        }

        DB::transaction(function () use ($rows): void {
            foreach (collect($rows)->pluck('category')->unique() as $categoryName) {
                Category::firstOrCreate(['name' => $categoryName]);
            }

            foreach ($rows as $row) {
                Question::create($row);
            }
        });

        return response()->json(['message' => 'Questions imported successfully.', 'imported' => count($rows)], 201);
    }

    /**
     * Download every question as a CSV file.
     */
    public function export(): StreamedResponse
    {
        $columns = [
            'question_text', 'option_a', 'option_b', 'option_c', 'option_d',
            'correct_option', 'category', 'country', 'difficulty', 'time_limit_seconds',
        ];

        return response()->streamDownload(function () use ($columns): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, $columns, ',', '"', '');

            foreach (Question::query()->orderBy('id')->cursor() as $question) {
                fputcsv($output, array_map(static fn (string $column) => $question->{$column}, $columns), ',', '"', '');
            }

            fclose($output);
        }, 'euroquiz-questions.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Display a listing of the resource, optionally filtered by category
     * and/or country so admins can organize questions by either.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'max:255'],
            'country' => ['sometimes', 'string', 'max:255'],
            'difficulty' => ['sometimes', 'string', 'in:easy,medium,hard'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $questions = Question::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where('question_text', 'like', '%'.$request->string('search')->toString().'%');
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('country'), fn ($query) => $query->where('country', $request->string('country')))
            ->when($request->filled('difficulty'), fn ($query) => $query->where('difficulty', $request->string('difficulty')))
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return QuestionAdminResource::collection($questions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $question = DB::transaction(function () use ($validated): Question {
            Category::firstOrCreate(['name' => $validated['category']]);

            return Question::create($validated);
        });

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
        $validated = $request->validated();
        DB::transaction(function () use ($question, $validated): void {
            if (isset($validated['category'])) {
                Category::firstOrCreate(['name' => $validated['category']]);
            }

            $question->update($validated);
        });

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
