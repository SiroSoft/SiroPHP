<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;

final class PostControllerMutationTest extends TestCase
{
    private function auth(): array
    {
        $app = $this->createApp();
        $email = 'postadmin-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Post Admin',
            'email' => $email,
            'password' => 'secret123',
        ]);
        \Siro\Core\Database::execute("UPDATE users SET role = 'admin' WHERE email = ?", [$email]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        return ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        try {
            \Siro\Core\Database::execute("DELETE FROM posts");
        } catch (\Throwable) {
        }
    }

    // ─── Index ───────────────────────────────────────────────────────

    public function testIndexReturnsPosts(): void
    {
        $this->get('/api/posts', $this->auth())->assertOk();
    }

    public function testIndexPagination(): void
    {
        $this->get('/api/posts?page=1&per_page=5', $this->auth())->assertOk();
    }

    public function testIndexWithLocaleFilter(): void
    {
        $this->get('/api/posts?locale=en', $this->auth())->assertOk();
    }

    // ─── Show ────────────────────────────────────────────────────────

    public function testShowInvalidId(): void
    {
        $this->get('/api/posts/0', $this->auth())->assertStatus(422);
    }

    public function testShowNonexistent(): void
    {
        $this->get('/api/posts/99999', $this->auth())->assertNotFound();
    }

    public function testShowValidPost(): void
    {
        $auth = $this->auth();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/posts', [
            'title' => 'Test Post Title',
            'body' => 'This is a test post body with enough content.',
            'locale' => 'en',
        ], $auth);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $this->get("/api/posts/$id", $auth)->assertOk();
        }
    }

    public function testShowForbiddenForOtherUser(): void
    {
        $auth1 = $this->authenticate();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/posts', [
            'title' => 'Post For Auth1',
            'body' => 'Body of post by auth1 user with enough chars.',
            'locale' => 'en',
        ], $auth1);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $app2 = $this->createApp();
            $email2 = 'other-' . uniqid() . '@test.com';
            $this->dispatch($app2, 'POST', '/api/auth/register', [
                'name' => 'Other',
                'email' => $email2,
                'password' => 'secret123',
            ]);
            $login2 = $this->dispatch($app2, 'POST', '/api/auth/login', [
                'email' => $email2,
                'password' => 'secret123',
            ]);
            $login2Json = $this->responseJson($login2);
            $token2 = $login2Json['data']['token'] ?? '';
            $auth2 = ['authorization' => 'Bearer ' . $token2, 'content-type' => 'application/json'];
            $this->get("/api/posts/$id", $auth2)->assertForbidden();
        }
    }

    // ─── Store ───────────────────────────────────────────────────────

    public function testStoreCreatesPost(): void
    {
        $this->post('/api/posts', [
            'title' => 'New Post',
            'body' => 'This is a new post with enough content to pass validation.',
            'locale' => 'en',
        ], $this->auth())->assertCreated();
    }

    public function testStoreVietnameseLocale(): void
    {
        $this->post('/api/posts', [
            'title' => 'Bai viet moi',
            'body' => 'Noi dung bai viet du dai de qua validation.',
            'locale' => 'vi',
        ], $this->auth())->assertCreated();
    }

    public function testStoreWithOptionalFields(): void
    {
        $this->post('/api/posts', [
            'title' => 'Full Post',
            'body' => 'Full post body with enough characters for validation.',
            'locale' => 'en',
            'status' => 'draft',
            'cover_image' => 'https://example.com/cover.jpg',
            'category_id' => 1,
            'excerpt' => 'An excerpt',
        ], $this->auth())->assertCreated();
    }

    public function testStoreValidationErrors(): void
    {
        $this->post('/api/posts', [], $this->auth())->assertStatus(422);
    }

    public function testStoreInvalidLocale(): void
    {
        $this->post('/api/posts', [
            'title' => 'Bad Locale',
            'body' => 'Body with enough content for validation.',
            'locale' => 'fr',
        ], $this->auth())->assertStatus(422);
    }

    // ─── Update ──────────────────────────────────────────────────────

    public function testUpdateInvalidId(): void
    {
        $this->put('/api/posts/0', ['title' => 'Test'], $this->auth())->assertStatus(422);
    }

    public function testUpdateNonexistent(): void
    {
        $this->put('/api/posts/99999', ['title' => 'Test'], $this->auth())->assertNotFound();
    }

    public function testUpdateValidPost(): void
    {
        $auth = $this->auth();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/posts', [
            'title' => 'Original Post',
            'body' => 'Original body with enough characters for the validation.',
            'locale' => 'en',
        ], $auth);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $this->put("/api/posts/$id", [
                'title' => 'Updated Post',
                'body' => 'Updated body with enough characters for the validation.',
                'excerpt' => 'New excerpt',
                'category_id' => 1,
            ], $auth)->assertOk();
        }
    }

    public function testUpdateForbiddenForOtherUser(): void
    {
        $auth1 = $this->authenticate();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/posts', [
            'title' => 'Post Not Yours',
            'body' => 'Body of post not owned by auth2.',
            'locale' => 'en',
        ], $auth1);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $app2 = $this->createApp();
            $email2 = 'upd-other-' . uniqid() . '@test.com';
            $this->dispatch($app2, 'POST', '/api/auth/register', [
                'name' => 'Other',
                'email' => $email2,
                'password' => 'secret123',
            ]);
            $login2 = $this->dispatch($app2, 'POST', '/api/auth/login', [
                'email' => $email2,
                'password' => 'secret123',
            ]);
            $login2Json = $this->responseJson($login2);
            $token2 = $login2Json['data']['token'] ?? '';
            $auth2 = ['authorization' => 'Bearer ' . $token2, 'content-type' => 'application/json'];
            $this->put("/api/posts/$id", ['title' => 'Hacked'], $auth2)->assertForbidden();
        }
    }

    // ─── Delete ──────────────────────────────────────────────────────

    public function testDeleteInvalidId(): void
    {
        $this->delete('/api/posts/0', $this->auth())->assertStatus(422);
    }

    public function testDeleteNonexistent(): void
    {
        $this->delete('/api/posts/99999', $this->auth())->assertNotFound();
    }

    public function testDeleteValidPost(): void
    {
        $auth = $this->auth();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/posts', [
            'title' => 'Delete Post',
            'body' => 'This post will be deleted with enough content.',
            'locale' => 'en',
        ], $auth);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $this->delete("/api/posts/$id", $auth)->assertNoContent();
        }
    }

    public function testDeleteForbiddenForOtherUser(): void
    {
        $auth1 = $this->authenticate();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/posts', [
            'title' => 'No Delete',
            'body' => 'This post should not be deleted by another user.',
            'locale' => 'en',
        ], $auth1);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $app2 = $this->createApp();
            $email2 = 'del-other-' . uniqid() . '@test.com';
            $this->dispatch($app2, 'POST', '/api/auth/register', [
                'name' => 'Other',
                'email' => $email2,
                'password' => 'secret123',
            ]);
            $login2 = $this->dispatch($app2, 'POST', '/api/auth/login', [
                'email' => $email2,
                'password' => 'secret123',
            ]);
            $login2Json = $this->responseJson($login2);
            $token2 = $login2Json['data']['token'] ?? '';
            $auth2 = ['authorization' => 'Bearer ' . $token2, 'content-type' => 'application/json'];
            $this->delete("/api/posts/$id", $auth2)->assertForbidden();
        }
    }
}
