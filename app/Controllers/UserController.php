<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\UserResource;
use Siro\Core\DB;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Validator;

final class UserController
{
    public function index(Request $request): Response
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 ? $perPage : 20;

        $result = DB::table('users')
            ->select(['id', 'name', 'email', 'created_at'])
            ->where('status', '=', 1)
            ->orderBy('id', 'desc')
            ->cache(60)
            ->paginate($perPage);

        $users = UserResource::collection($result['data']);

        return Response::success($users, 'Users fetched successfully', 200, $result['meta']);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id', 0);
        if ($id <= 0) {
            return Response::error('Invalid user id', 422, [
                'id' => ['Id must be a positive integer'],
            ]);
        }

        $user = DB::table('users')
            ->select(['id', 'name', 'email', 'created_at'])
            ->where('id', '=', $id)
            ->cache(60)
            ->first();

        if ($user === null) {
            return Response::error('User not found', 404);
        }

        return Response::success((new UserResource($user))->toArray(), 'User fetched successfully');
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

        $insertedId = DB::table('users')->insert([
            'name' => (string) $request->input('name'),
            'email' => (string) $request->input('email'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $created = DB::table('users')
            ->select(['id', 'name', 'email', 'created_at'])
            ->where('id', (int) $insertedId)
            ->first();

        return Response::created(
            $created !== null ? (new UserResource($created))->toArray() : ['id' => $insertedId],
            'User created successfully'
        );
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

        $payload = [];
        if ($request->input('name') !== null) {
            $payload['name'] = (string) $request->input('name');
        }
        if ($request->input('email') !== null) {
            $payload['email'] = (string) $request->input('email');
        }

        $affected = DB::table('users')
            ->where('id', '=', $id)
            ->update($payload);

        if ($affected === 0) {
            return Response::error('User not found', 404);
        }

        $updated = DB::table('users')
            ->select(['id', 'name', 'email', 'created_at'])
            ->where('id', '=', $id)
            ->first();

        return Response::success(
            $updated !== null ? (new UserResource($updated))->toArray() : null,
            'User updated successfully'
        );
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id', 0);
        if ($id <= 0) {
            return Response::error('Invalid user id', 422, [
                'id' => ['Id must be a positive integer'],
            ]);
        }

        $deleted = DB::table('users')
            ->where('id', '=', $id)
            ->delete();

        if ($deleted === 0) {
            return Response::error('User not found', 404);
        }

        return Response::success(null, 'User deleted successfully');
    }

    public function destroy(Request $request): Response
    {
        return $this->delete($request);
    }
}
