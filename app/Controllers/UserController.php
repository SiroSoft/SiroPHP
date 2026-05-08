<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use App\Resources\UserResource;
use Siro\Core\Request;
use Siro\Core\Response;

/**
 * User management controller (admin).
 *
 * Requires authentication. Provides full CRUD with
 * email uniqueness validation and password hashing via UserService.
 */
final class UserController
{
    public function __construct(private readonly UserService $service)
    {
    }

    /** List users with pagination. */
    public function index(Request $request): Response
    {
        $page = max(1, $request->queryInt('page', 1));
        $perPage = min(100, max(1, $request->queryInt('per_page', 15)));

        $result = $this->service->getAll($page, $perPage);

        return Response::paginated(
            UserResource::collection($result['data']),
            $result['meta'],
            'Users retrieved',
        );
    }

    /** Get a single user by ID. Password field is excluded from response. */
    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = $this->service->getById($id);

        if ($user === null) {
            return Response::error('User not found', 404);
        }

        return Response::success(UserResource::make($user), 'User retrieved');
    }

    /** Create a new user. Email uniqueness is enforced. */
    public function store(Request $request): Response
    {
        $request->validate([
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ]);

        try {
            $userData = $this->service->create($request->all());
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Email has already been taken') {
                return Response::error('Validation failed', 422, [
                    'email' => ['Email has already been taken'],
                ]);
            }
            return Response::error('Unable to create user', 500);
        }

        return Response::created(UserResource::make($userData), 'User created');
    }

    /** Update user fields. Only provided fields are updated. */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');

        $rules = [
            'name' => 'min:3|max:120',
            'email' => 'email|max:255',
            'password' => 'min:6|max:255',
        ];
        $request->validate($rules);

        try {
            $userData = $this->service->update($id, $request->all());
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'No fields to update') {
                return Response::error('No fields to update', 400);
            }
            if ($e->getMessage() === 'Email has already been taken') {
                return Response::error('Validation failed', 422, [
                    'email' => ['Email has already been taken'],
                ]);
            }
            return Response::error('Unable to update user', 500);
        }

        if ($userData === null) {
            return Response::error('User not found', 404);
        }

        return Response::success(UserResource::make($userData), 'User updated');
    }

    /** Delete a user. */
    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');

        return $this->service->delete($id)
            ? Response::noContent()
            : Response::error('User not found', 404);
    }
}
