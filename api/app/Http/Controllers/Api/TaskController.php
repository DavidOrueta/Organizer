<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->membership($request->user(), $project);
        $tasks = $project->tasks()->with(['assignee:id,name', 'comments.author:id,name']);

        if ($request->filled('status')) {
            $tasks->where('status', $request->input('status'));
        }
        if ($request->filled('assignee_id')) {
            $tasks->where('assignee_id', $request->integer('assignee_id'));
        }
        if ($request->filled('due_from')) {
            $tasks->whereDate('due_at', '>=', $request->input('due_from'));
        }
        if ($request->filled('due_to')) {
            $tasks->whereDate('due_at', '<=', $request->input('due_to'));
        }
        if ($request->boolean('overdue')) {
            $tasks->whereDate('due_at', '<', today())->where('status', '!=', 'done');
        }
        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $tasks->where(fn ($query) => $query->where('title', 'like', $search)->orWhere('description', 'like', $search));
        }

        return response()->json($tasks->orderBy('due_at')->paginate(50));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $membership = $this->membership($request->user(), $project);
        $data = $this->validatedTask($request, false);
        $data['assignee_id'] = $membership->pivot->role === 'admin'
            ? ($data['assignee_id'] ?? null)
            : $request->user()->id;
        $this->ensureAssigneeIsMember($project, $data['assignee_id']);

        $task = $project->tasks()->create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $task->load('assignee:id,name')], 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $membership = $this->membership($request->user(), $task->project);
        $isAdmin = $membership->pivot->role === 'admin';
        abort_unless($isAdmin || $task->assignee_id === $request->user()->id, 403);

        $data = $this->validatedTask($request, true);
        abort_if(! $isAdmin && array_key_exists('assignee_id', $data), 403);
        if (array_key_exists('assignee_id', $data)) {
            $this->ensureAssigneeIsMember($task->project, $data['assignee_id']);
        }

        $task->update($data);

        return response()->json(['data' => $task->fresh()->load('assignee:id,name')]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $membership = $this->membership($request->user(), $task->project);
        abort_unless($membership->pivot->role === 'admin', 403);
        $task->delete();

        return response()->json([], 204);
    }

    private function validatedTask(Request $request, bool $partial): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'status' => ['sometimes', 'in:todo,in_progress,done'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ]);
    }

    private function membership(User $user, Project $project): User
    {
        return $project->members()->whereKey($user->id)->firstOrFail();
    }

    private function ensureAssigneeIsMember(Project $project, ?int $assigneeId): void
    {
        if ($assigneeId !== null) {
            abort_unless($project->members()->whereKey($assigneeId)->exists(), 422, 'La persona asignada no pertenece al proyecto.');
        }
    }
}