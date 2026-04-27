<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Validator;
use Throwable;

final class UserController
{
    private readonly UserService $users;

    public function __construct()
    {
        $this->users = new UserService();
    }

    public function index(Request $request): Response
    {
        $users = $this->users->listUsers();

        return Response::success($users, 'Users fetched successfully', 200, [
            'count' => count($users),
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id', 0);
        if ($id <= 0) {
            return Response::error('Invalid user id', 422, [
                'id' => ['Id must be a positive integer'],
            ]);
        }

        $user = $this->users->findById($id);
        if ($user === null) {
            return Response::error('User not found', 404);
        }

        return Response::success($user, 'User fetched successfully');
    }

    public function store(Request $request): Response
    {
        $errors = Validator::make($request->body(), [
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
        ]);

        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        try {
            $created = $this->users->createUser([
                'name' => (string) $request->input('name'),
                'email' => (string) $request->input('email'),
            ]);

            return Response::created($created, 'User created successfully');
        } catch (Throwable $e) {
            return Response::error('Failed to create user', 500, [
                'database' => [$e->getMessage()],
            ]);
        }
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id', 0);
        if ($id <= 0) {
            return Response::error('Invalid user id', 422, [
                'id' => ['Id must be a positive integer'],
            ]);
        }

        $errors = Validator::make($request->body(), [
            'name' => 'min:3|max:120',
            'email' => 'email|max:255',
        ]);
        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        if ($request->input('name') === null && $request->input('email') === null) {
            return Response::error('Nothing to update', 422, [
                'body' => ['Provide at least one field: name or email'],
            ]);
        }

        try {
            $updated = $this->users->updateUser($id, [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
            ]);

            if ($updated === null) {
                return Response::error('User not found', 404);
            }

            return Response::success($updated, 'User updated successfully');
        } catch (Throwable $e) {
            return Response::error('Failed to update user', 500, [
                'database' => [$e->getMessage()],
            ]);
        }
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id', 0);
        if ($id <= 0) {
            return Response::error('Invalid user id', 422, [
                'id' => ['Id must be a positive integer'],
            ]);
        }

        try {
            $deleted = $this->users->deleteUser($id);
            if (!$deleted) {
                return Response::error('User not found', 404);
            }

            return Response::noContent();
        } catch (Throwable $e) {
            return Response::error('Failed to delete user', 500, [
                'database' => [$e->getMessage()],
            ]);
        }
    }
}
