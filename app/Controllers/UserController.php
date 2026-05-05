<?php

declare(strict_types=1);

namespace App\Controllers;

<<<<<<< HEAD
use App\Models\User;
use App\Resources\UserResource;
use Siro\Core\Request;
use Siro\Core\Response;
=======
use App\Resources\UserResource;
use Siro\Core\DB;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Validator;
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0

final class UserController
{
    public function index(Request $request): Response
    {
<<<<<<< HEAD
        $page = max(1, $request->queryInt('page', 1));
        $perPage = min(100, max(1, $request->queryInt('per_page', 15)));

        $result = User::paginate($perPage, $page);

        return Response::paginated(
            UserResource::collection($result['data']),
            $result['meta'],
            'Users retrieved',
        );
=======
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
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
    }

    public function show(Request $request): Response
    {
<<<<<<< HEAD
        $id = (int) $request->param('id');
        $user = User::find($id);
=======
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
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0

        if ($user === null) {
            return Response::error('User not found', 404);
        }

<<<<<<< HEAD
        return Response::success(UserResource::make($user), 'User retrieved');
=======
        return Response::success((new UserResource($user))->toArray(), 'User fetched successfully');
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
    }

    public function store(Request $request): Response
    {
<<<<<<< HEAD
        $request->validate([
=======
        $errors = Validator::make($request->body(), [
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ]);

<<<<<<< HEAD
        $email = strtolower(trim($request->string('email')));
        $exists = User::where('email', '=', $email)->limit(1)->get();

        if ($exists !== []) {
            return Response::error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        }

        $passwordHash = password_hash($request->string('password'), PASSWORD_DEFAULT);
=======
        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        $passwordHash = password_hash((string) $request->input('password'), PASSWORD_DEFAULT);
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
        if ($passwordHash === false) {
            return Response::error('Unable to create user', 500);
        }

<<<<<<< HEAD
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $email,
            'password' => $passwordHash,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return Response::created(UserResource::make($user), 'User created');
=======
        $insertedId = DB::table('users')->insert([
            'name' => (string) $request->input('name'),
            'email' => (string) $request->input('email'),
            'password' => $passwordHash,
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
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
    }

    public function update(Request $request): Response
    {
<<<<<<< HEAD
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
        return Response::success(UserResource::make($user), 'User updated');
=======
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
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
    }

    public function delete(Request $request): Response
    {
<<<<<<< HEAD
        $id = (int) $request->param('id');
        $user = User::find($id);

        if ($user === null) {
            return Response::error('User not found', 404);
        }

        $user->delete();
        return Response::success(null, 'User deleted');
=======
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
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
    }
}
