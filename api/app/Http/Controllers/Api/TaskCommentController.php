<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        $this->membership($request->user(), $task);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return response()->json(['data' => $comment->load('author:id,name')], 201);
    }

    private function membership(User $user, Task $task): void
    {
        abort_unless($task->project->members()->whereKey($user->id)->exists(), 403);
    }
}