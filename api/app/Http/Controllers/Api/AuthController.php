<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($data) {
            $data['role'] = User::query()->doesntExist() ? 'admin' : 'member';
            $user = User::create($data);
            if ($user->role === 'admin') {
                $project = Project::create([
                    'owner_id' => $user->id,
                    'name' => 'Mi primer proyecto',
                    'key' => 'ORG',
                ]);
                $project->members()->attach($user->id, ['role' => 'admin']);
            }

            return $user;
        });

        return response()->json($this->sessionPayload($user), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Las credenciales no son válidas.'], 422);
        }

        return response()->json($this->sessionPayload($user));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    private function sessionPayload(User $user): array
    {
        return [
            'user' => $user,
            'token' => $user->createToken('organizer-web')->plainTextToken,
        ];
    }
}