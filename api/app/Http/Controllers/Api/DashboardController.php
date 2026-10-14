<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $tasks = Task::whereHas('project.members', fn ($query) => $query->whereKey($user->id));

        return response()->json([
            'projects_count' => $user->projects()->count(),
            'tasks_by_status' => [
                'todo' => (clone $tasks)->where('status', 'todo')->count(),
                'in_progress' => (clone $tasks)->where('status', 'in_progress')->count(),
                'done' => (clone $tasks)->where('status', 'done')->count(),
            ],
            'overdue_count' => (clone $tasks)->whereDate('due_at', '<', today())->where('status', '!=', 'done')->count(),
            'workload' => (clone $tasks)
                ->whereNotNull('assignee_id')
                ->where('status', '!=', 'done')
                ->selectRaw('assignee_id, count(*) as task_count')
                ->with('assignee:id,name')
                ->groupBy('assignee_id')
                ->get(),
        ]);
    }
}