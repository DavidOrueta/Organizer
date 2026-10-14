<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = $request->user()->projects()
            ->withCount('tasks')
            ->with('members:id,name,email')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $projects]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'admin', 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'key' => ['required', 'alpha_dash', 'max:8', 'unique:projects,key'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $project = DB::transaction(function () use ($request, $data) {
            $project = Project::create([...$data, 'owner_id' => $request->user()->id]);
            $project->members()->attach($request->user()->id, ['role' => 'admin']);

            return $project;
        });

        return response()->json(['data' => $project->load('members:id,name,email')], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->membership($request->user(), $project);

        return response()->json([
            'data' => $project->load(['members:id,name,email', 'owner:id,name'])->loadCount('tasks'),
        ]);
    }

    public function addMember(Request $request, Project $project): JsonResponse
    {
        $membership = $this->membership($request->user(), $project);
        abort_unless($membership->pivot->role === 'admin', 403);

        $data = $request->validate([
            'user_id' => ['nullable', 'required_without:email', 'integer', 'exists:users,id'],
            'email' => ['nullable', 'required_without:user_id', 'email', 'exists:users,email'],
            'role' => ['sometimes', 'in:admin,member'],
        ]);
        $member = isset($data['user_id'])
            ? User::findOrFail($data['user_id'])
            : User::where('email', $data['email'])->firstOrFail();
        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => $data['role'] ?? 'member'],
        ]);

        return response()->json(['data' => $member->only('id', 'name', 'email')], 201);
    }

    private function membership(User $user, Project $project): User
    {
        return $project->members()->whereKey($user->id)->firstOrFail();
    }
}