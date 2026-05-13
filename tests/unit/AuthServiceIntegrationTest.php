<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\AuthController;
use App\Services\UserService;

final class AuthServiceIntegrationTest extends TestCase
{
    public function testAuthControllerRegisterUsesUserService(): void
    {
        $source = $this->getMethodSource(AuthController::class, 'register');

        $this->assertStringContainsString(
            'userService->getByEmail',
            $source,
            'register() must use $this->userService->getByEmail()'
        );
        $this->assertStringContainsString(
            'userService->createUser',
            $source,
            'register() must use $this->userService->createUser()'
        );
    }

    public function testAuthControllerLoginUsesUserService(): void
    {
        $source = $this->getMethodSource(AuthController::class, 'login');

        $this->assertStringContainsString(
            'userService->getByEmail',
            $source,
            'login() must use $this->userService->getByEmail()'
        );
    }

    public function testAuthControllerVerifyEmailUsesUserServiceVerifyEmail(): void
    {
        $source = $this->getMethodSource(AuthController::class, 'verifyEmail');

        $this->assertStringContainsString(
            'userService->verifyEmail',
            $source,
            'verifyEmail() must use $this->userService->verifyEmail()'
        );
    }

    public function testAuthControllerForgotPasswordUsesUserServiceInitiatePasswordReset(): void
    {
        $source = $this->getMethodSource(AuthController::class, 'forgotPassword');

        $this->assertStringContainsString(
            'userService->initiatePasswordReset',
            $source,
            'forgotPassword() must use $this->userService->initiatePasswordReset()'
        );
    }

    public function testAuthControllerResetPasswordUsesUserServiceResetPassword(): void
    {
        $source = $this->getMethodSource(AuthController::class, 'resetPassword');

        $this->assertStringContainsString(
            'userService->resetPassword',
            $source,
            'resetPassword() must use $this->userService->resetPassword()'
        );
    }

    public function testUserServiceHasAllRequiredMethods(): void
    {
        $requiredMethods = [
            'getByEmail',
            'createUser',
            'getTokenVersion',
            'verifyEmail',
            'initiatePasswordReset',
            'resetPassword',
            'incrementTokenVersion',
        ];

        $reflection = new \ReflectionClass(UserService::class);

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "UserService must have method: {$method}()"
            );

            $methodReflection = $reflection->getMethod($method);

            $this->assertFalse(
                $methodReflection->isStatic(),
                "UserService::{$method}() must NOT be static (instance DI)"
            );

            $this->assertTrue(
                $methodReflection->isPublic(),
                "UserService::{$method}() must be public"
            );
        }
    }

    private function getMethodSource(string $className, string $methodName): string
    {
        $reflection = new \ReflectionMethod($className, $methodName);
        $filename = $reflection->getFileName();
        $startLine = $reflection->getStartLine() - 1;
        $endLine = $reflection->getEndLine();
        $length = $endLine - $startLine;

        $source = file($filename);

        return implode('', array_slice($source, $startLine, $length));
    }
}
