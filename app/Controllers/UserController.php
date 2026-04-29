<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Siro\Core\Request;
use Siro\Core\Response;

final class UserController
{
    public function index(Request $request): Response
    {
        $page = max(1, $request->queryInt('page', 1));
        $perPage = min(100, max(1, $request->queryInt('per_page', 15)));

        $users = User::paginate($perPage, $page);
        $total = User::query()->count();

        return Response::paginated($users, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) ceil($total / $perPage),
        ], 'Users retrieved');
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = User::find($id);

        if ($user === null) {
            return Response::error('User not found', 404);
        }

        return Response::success($user->toArray(), 'User retrieved');
    }

    public function store(Request $request): Response
    {
        $request->validate([
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ]);

        $email = strtolower(trim($request->string('email')));
        $exists = User::where('email', '=', $email)->limit(1)->get();

        if ($exists !== []) {
            return Response::error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        }

        $passwordHash = password_hash($request->string('password'), PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            return Response::error('Unable to create user', 500);
        }

        $user = User::create([
            'name' => $request->string('name'),
            'email' => $email,
            'password' => $passwordHash,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return Response::created($user->toArray(), 'User created');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = User::find($id);

        if ($user === null) {
            return Response::error('User not found', 404);
        }

        $rules = [
            'name' => 'min:3|max:120',
            'email' => 'email|max:255',
            'password' => 'min:6|max:255',
        ];
        $request->validate($rules);

        $data = [];

        $name = $request->input('name');
        if ($name !== null) {
            $data['name'] = $name;
        }

        $email = $request->input('email');
        if ($email !== null) {
            $data['email'] = strtolower(trim((string) $email));
        }

        $password = $request->input('password');
        if ($password !== null) {
            $passwordHash = password_hash((string) $password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                return Response::error('Unable to update user', 500);
            }
            $data['password'] = $passwordHash;
        }

        if ($data === []) {
            return Response::error('No fields to update', 400);
        }

        $user->update($data);
        return Response::success($user->toArray(), 'User updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = User::find($id);

        if ($user === null) {
            return Response::error('User not found', 404);
        }

        $user->delete();
        return Response::success(null, 'User deleted');
    }
}
