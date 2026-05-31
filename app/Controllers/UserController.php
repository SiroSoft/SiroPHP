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

    /**
     * List all users with pagination and optional status/role filtering.
     *
     * Admin only. Supports status (active/inactive/suspended) and role filters.
     * Rate limited: 60 requests per minute.
     *
     * GET /api/users?page=1&per_page=20&status=active&role=admin
     *
     * @param Request $request Incoming HTTP request with optional query params
     * @return Response Paginated list of users
     */
    public function index(Request $request): Response
    {
        $forbidden = $this->requireAdmin($request);
        if ($forbidden !== null) return $forbidden;

        $page = max(1, $request->queryInt('page', 1));
        $perPage = min(100, max(1, $request->queryInt('per_page', 20)));

        $filters = [];
        $status = $request->query('status');
        if (is_string($status) && $status !== '') {
            $filters['status'] = match ($status) { 'inactive' => 0, 'suspended' => 2, default => 1 };
        }
        $role = $request->query('role');
        if (is_string($role) && $role !== '') {
            $filters['role'] = $role;
        }

        $result = $this->service->getAll($page, $perPage, $filters);
        $data = [];
        foreach ($result['data'] as $item) {
            $data[] = $item->toArray();
        }
        return $this->paginated(
            UserResource::collection($data),
            $result['meta'],
            'Users retrieved',
        );
    }

    /**
     * Get a single user by ID.
     *
     * Users can view their own profile; admins can view any user.
     *
     * GET /api/users/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response User detail (200) or error (403/404/422)
     */
    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;
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

    /**
     * Update the authenticated user's profile (name, email, avatar, phone).
     *
     * Handles duplicate email detection.
     *
     * PUT /api/profile
     * Body: { name?: string, email?: string, avatar?: string, phone?: string }
     *
     * @param Request $request Incoming HTTP request with profile updates
     * @return Response Updated user (200) or error (400/401/422)
     */
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

        if (isset($data['email']) && is_string($data['email'])) {
            $existing = $this->service->getByEmail($data['email']);
            if ($existing !== null && isset($existing['id']) && is_numeric($existing['id']) && (int) $existing['id'] !== $userId) {
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

    /**
     * Create a new user (admin panel).
     *
     * Admin only. Accepts name, email, password, plus optional role, status, avatar, phone.
     *
     * POST /api/users
     * Body: { name: string, email: string, password: string, role?: string, status?: string, avatar?: string, phone?: string }
     *
     * @param Request $request Incoming HTTP request with user data
     * @return Response Created user (201) or error (403/422)
     */
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
        if (isset($rawBody['status']) && is_string($rawBody['status'])) {
            $data['status'] = match ($rawBody['status']) { 'inactive' => 0, 'suspended' => 2, default => 1 };
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

    /**
     * Update a user (name, email, password, role, status, avatar, phone).
     *
     * Users can update their own profile; admins can update any user.
     * Requires current_password when changing password.
     *
     * PUT /api/users/{id}
     * Body: { name?: string, email?: string, password?: string, current_password?: string, role?: string, status?: string, avatar?: string, phone?: string }
     *
     * @param Request $request Incoming HTTP request with user updates
     * @return Response Updated user (200) or error (400/403/404/422)
     */
    public function update(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;

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
        if (isset($rawBody['status']) && is_string($rawBody['status'])) {
            $data['status'] = match ($rawBody['status']) { 'inactive' => 0, 'suspended' => 2, default => 1 };
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
            $currentPassword = is_string($data['current_password'] ?? null) ? $data['current_password'] : '';
            $existingPassword = $existingUser !== null ? $existingUser->getAttribute('password') : '';
            if (!is_string($existingPassword) || $existingPassword === '' || !password_verify($currentPassword, $existingPassword)) {
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

    /**
     * Delete a user by ID.
     *
     * Users can delete their own account; admins can delete any user.
     * Self-deletion is not allowed (must contact an admin).
     *
     * DELETE /api/users/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response Empty (204) or error (403/404/422)
     */
    public function delete(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = is_numeric($rawId) ? (int) $rawId : 0;

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
