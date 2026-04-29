<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Resources\UserResource;
use Siro\Core\Request;
use Siro\Core\Response;

final class UserController
{
    public function index(Request $request): Response
    {
        $perPage = $request->queryInt('per_page', 20);
        $result = User::paginate($perPage);

        return Response::paginated(
            UserResource::collection($result['data']),
            $result['meta']
        );
    }

    public function show(Request $request): Response
    {
        $id = $request->int('id');
        $user = User::find($id);

        if ($user === null) {
            return Response::error('User not found', 404);
        }

        return Response::success($user->toArray(), 'User fetched successfully');
    }

    public function store(Request $request): Response
    {
        $request->validate([
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ]);

        $email = strtolower(trim($request->string('email')));

        $rows = User::where('email', '=', $email)->limit(1)->get();
        $existing = $rows[0] ?? null;
        if ($existing !== null) {
            return Response::error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        }

        $user = User::create([
            'name' => $request->string('name'),
            'email' => $email,
            'password' => password_hash($request->string('password'), PASSWORD_DEFAULT),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return Response::created($user->toArray(), 'User created successfully');
    }

    public function update(Request $request): Response
    {
        $id = $request->int('id');
        $user = User::find($id);

        if ($user === null) {
            return Response::error('User not found', 404);
        }

        $request->validate([
            'name' => 'min:3|max:120',
            'email' => 'email|max:255',
        ]);

        $payload = [];
        if ($request->input('name') !== null) {
            $payload['name'] = $request->string('name');
        }
        if ($request->input('email') !== null) {
            $payload['email'] = $request->string('email');
        }

        if ($payload === []) {
            return Response::error('Nothing to update', 422, [
                'body' => ['Provide at least one field: name or email'],
            ]);
        }

        $user->update($payload);

        return Response::success($user->toArray(), 'User updated successfully');
    }

    public function delete(Request $request): Response
    {
        $id = $request->int('id');
        $user = User::find($id);

        if ($user === null) {
            return Response::error('User not found', 404);
        }

        $user->delete();
        return Response::success(null, 'User deleted successfully');
    }

    public function destroy(Request $request): Response
    {
        return $this->delete($request);
    }
}
