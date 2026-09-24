<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Question;
use App\Models\Result;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardStatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $activityStart = Carbon::today()->subDays(6);
        $activityRows = Result::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as results, COALESCE(SUM(score), 0) as score')
            ->whereDate('created_at', '>=', $activityStart)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $activity = collect(range(0, 6))->map(function (int $daysAgo) use ($activityRows): array {
            $date = Carbon::today()->subDays(6 - $daysAgo)->toDateString();
            $row = $activityRows->get($date);

            return [
                'date' => $date,
                'label' => Carbon::parse($date)->format('M j'),
                'results' => (int) ($row->results ?? 0),
                'score' => (int) ($row->score ?? 0),
            ];
        })->all();

        return response()->json([
            'data' => [
                'totals' => [
                    'questions' => Question::count(),
                    'users' => User::count(),
                    'results' => Result::count(),
                    'categories' => Category::count(),
                    'score' => (int) Result::sum('score'),
                    'average_score' => round((float) Result::avg('score'), 1),
                ],
                'questions' => [
                    'by_country' => Question::query()
                        ->select('country', DB::raw('COUNT(*) as count'))
                        ->groupBy('country')
                        ->orderByDesc('count')
                        ->get()
                        ->map(fn ($row): array => ['label' => $row->country, 'count' => (int) $row->count])
                        ->values(),
                    'by_category' => Question::query()
                        ->select('category', DB::raw('COUNT(*) as count'))
                        ->groupBy('category')
                        ->orderByDesc('count')
                        ->get()
                        ->map(fn ($row): array => ['label' => $row->category, 'count' => (int) $row->count])
                        ->values(),
                    'by_difficulty' => Question::query()
                        ->select('difficulty', DB::raw('COUNT(*) as count'))
                        ->groupBy('difficulty')
                        ->orderBy('difficulty')
                        ->get()
                        ->map(fn ($row): array => ['label' => ucfirst($row->difficulty), 'count' => (int) $row->count])
                        ->values(),
                ],
                'activity' => $activity,
            ],
        ]);
    }
}
