<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\DuplicateEmailException;
use App\Exceptions\NoFieldsToUpdateException;
use App\Resources\UserResource;
use App\Services\UserService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class UserController extends Controller
{
    public function __construct(private readonly UserService $service)
    {
    }

    public function index(Request $request): Response
    {
        $page = max(1, $request->queryInt('page', 1));
        $perPage = min(100, max(1, $request->queryInt('per_page', 15)));

        $result = $this->service->getAll($page, $perPage);

        return $this->paginated(
            UserResource::collection($result['data']),
            $result['meta'],
            'Users retrieved',
        );
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return $this->error('Invalid id', 422);
        $user = $this->service->getById($id);

        if ($user === null) {
            return $this->error('User not found', 404);
        }

        return $this->success(UserResource::make($user), 'User retrieved');
    }

    public function store(Request $request): Response
    {
        $request->validate([
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ]);

        try {
            $userData = $this->service->create($request->all());
        } catch (DuplicateEmailException) {
            return $this->error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        } catch (\RuntimeException) {
            return $this->error('Unable to create user', 500);
        }

        return $this->created(UserResource::make($userData), 'User created');
    }

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
        } catch (DuplicateEmailException) {
            return $this->error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        } catch (NoFieldsToUpdateException) {
            return $this->error('No fields to update', 400);
        } catch (\RuntimeException) {
            return $this->error('Unable to update user', 500);
        }

        if ($userData === null) {
            return $this->error('User not found', 404);
        }

        return $this->success(UserResource::make($userData), 'User updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');

        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('User not found', 404);
    }
}
