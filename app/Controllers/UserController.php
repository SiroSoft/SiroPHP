<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\DuplicateEmailException;
use App\Exceptions\NoFieldsToUpdateException;
use App\Resources\UserResource;
use App\Role;
use App\Services\UserService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class UserController extends Controller
{
    public function __construct(private readonly UserService $service)
    {
    }

    // Rate limited: 60 requests per minute. Admin only.
    public function index(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $page = max(1, $request->queryInt('page', 1));
        $perPage = min(100, max(1, $request->queryInt('per_page', 20)));

        $filters = [];
        $status = $request->query('status');
        if ($status !== null && $status !== '') {
            $filters['status'] = match ((string) $status) { 'inactive' => 0, 'suspended' => 2, default => 1 };
        }
        $role = $request->query('role');
        if ($role !== null && $role !== '') {
            $filters['role'] = (string) $role;
        }

        $result = $this->service->getAll($page, $perPage, $filters);

        return $this->paginated(
            UserResource::collection($result['data']),
            $result['meta'],
            'Users retrieved',
        );
    }

    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }
        if ($currentUserId !== $id && $currentUserRole !== Role::ADMIN) {
            return $this->error('Forbidden', 403);
        }

        $user = $this->service->getById($id);

        if ($user === null) {
            return $this->error('User not found', 404);
        }

        return $this->success(UserResource::make($user), 'User retrieved');
    }

    public function updateProfile(Request $request): Response
    {
        $user = $request->user();
        $userId = is_numeric($user['id'] ?? null) ? (int) $user['id'] : 0;
        if ($userId <= 0) {
            return $this->error('Unauthorized', 401);
        }

        $data = $this->validate([
            'name' => 'min:2|max:255',
            'email' => 'email|max:255',
        ]);

        $rawBody = $request->all();
        if (isset($rawBody['avatar'])) {
            $data['avatar'] = $rawBody['avatar'];
        }
        if (isset($rawBody['phone'])) {
            $data['phone'] = $rawBody['phone'];
        }

        if ($data === []) {
            return $this->error('No fields to update', 400);
        }

        if (isset($data['email'])) {
            $existing = $this->service->getByEmail($data['email']);
            if ($existing !== null && (int) ($existing['id'] ?? 0) !== $userId) {
                return $this->error('Validation failed', 422, [
                    'email' => ['Email has already been taken'],
                ]);
            }
        }

        try {
            $updated = $this->service->update($userId, $data);
        } catch (DuplicateEmailException) {
            return $this->error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        } catch (NoFieldsToUpdateException) {
            return $this->error('No fields to update', 400);
        }

        if ($updated === null) {
            return $this->error('Update failed', 400);
        }

        return $this->success(UserResource::make($updated), 'Profile updated');
    }

    // Admin only.
    public function store(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $data = $this->validate([
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|max:255',
        ]);

        $rawBody = $request->all();
        if (isset($rawBody['role'])) {
            $data['role'] = $rawBody['role'];
        }
        if (isset($rawBody['status'])) {
            $data['status'] = match ((string) $rawBody['status']) { 'inactive' => 0, 'suspended' => 2, default => 1 };
        }
        if (isset($rawBody['avatar'])) {
            $data['avatar'] = $rawBody['avatar'];
        }
        if (isset($rawBody['phone'])) {
            $data['phone'] = $rawBody['phone'];
        }

        try {
            $user = $this->service->create($data);
        } catch (DuplicateEmailException) {
            return $this->error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        }

        return $this->created(UserResource::make($user), 'User created');
    }

    public function update(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = (int) $rawId;

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }
        if ($currentUserId !== $id && $currentUserRole !== Role::ADMIN) {
            return $this->error('Forbidden', 403);
        }

        $data = $this->validate([
            'name' => 'min:3|max:120',
            'email' => 'email|max:255',
            'password' => 'min:8|max:255',
            'current_password' => 'required_with:password',
        ]);

        $rawBody = $request->all();
        if (isset($rawBody['role'])) {
            $data['role'] = $rawBody['role'];
        }
        if (isset($rawBody['status'])) {
            $data['status'] = match ((string) $rawBody['status']) { 'inactive' => 0, 'suspended' => 2, default => 1 };
        }
        if (isset($rawBody['avatar'])) {
            $data['avatar'] = $rawBody['avatar'];
        }
        if (isset($rawBody['phone'])) {
            $data['phone'] = $rawBody['phone'];
        }
        if (isset($rawBody['cover_image'])) {
            $data['cover_image'] = $rawBody['cover_image'];
        }

        if (isset($data['password'])) {
            $existingUser = $this->service->getById($id);
            $existingPassword = is_array($existingUser) ? ($existingUser['password'] ?? '') : '';
            if (!is_string($existingPassword) || $existingPassword === '' || !password_verify(strval($data['current_password'] ?? ''), $existingPassword)) {
                return $this->error('Validation failed', 422, [
                    'current_password' => ['Current password is incorrect'],
                ]);
            }
        }

        try {
            $userData = $this->service->update($id, $data);
        } catch (DuplicateEmailException) {
            return $this->error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        } catch (NoFieldsToUpdateException) {
            return $this->error('No fields to update', 400);
        }

        if ($userData === null) {
            return $this->error('User not found', 404);
        }

        return $this->success(UserResource::make($userData), 'User updated');
    }

    public function delete(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = (int) $rawId;

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }
        if ($currentUserId !== $id && $currentUserRole !== Role::ADMIN) {
            return $this->error('Forbidden', 403);
        }

        if ($currentUserId === $id) {
            return $this->error('Self-deletion is not allowed. Contact an administrator.', 403);
        }

        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('User not found', 404);
    }

    private function requireAdmin(Request $request): ?Response
    {
        $user = $request->user();
        $role = is_array($user) && isset($user['role']) && is_string($user['role']) ? $user['role'] : Role::USER;
        if ($role !== Role::ADMIN) {
            return Response::error('Forbidden', 403);
        }
        return null;
    }
}
