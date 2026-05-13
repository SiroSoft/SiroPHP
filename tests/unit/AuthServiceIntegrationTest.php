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
            'UserService::getByEmail',
            $source,
            'register() must use UserService::getByEmail()'
        );
        $this->assertStringContainsString(
            'UserService::createUser',
            $source,
            'register() must use UserService::createUser()'
        );
    }

    public function testAuthControllerLoginUsesUserService(): void
    {
        $source = $this->getMethodSource(AuthController::class, 'login');

        $this->assertStringContainsString(
            'UserService::getByEmail',
            $source,
            'login() must use UserService::getByEmail()'
        );
    }

    public function testAuthControllerVerifyEmailUsesUserServiceVerifyEmail(): void
    {
        $source = $this->getMethodSource(AuthController::class, 'verifyEmail');

        $this->assertStringContainsString(
            'UserService::verifyEmail',
            $source,
            'verifyEmail() must use UserService::verifyEmail()'
        );
    }

    public function testAuthControllerForgotPasswordUsesUserServiceInitiatePasswordReset(): void
    {
        $source = $this->getMethodSource(AuthController::class, 'forgotPassword');

        $this->assertStringContainsString(
            'UserService::initiatePasswordReset',
            $source,
            'forgotPassword() must use UserService::initiatePasswordReset()'
        );
    }

    public function testAuthControllerResetPasswordUsesUserServiceResetPassword(): void
    {
        $source = $this->getMethodSource(AuthController::class, 'resetPassword');

        $this->assertStringContainsString(
            'UserService::resetPassword',
            $source,
            'resetPassword() must use UserService::resetPassword()'
        );
    }

    public function testUserServiceHasAllRequiredStaticMethods(): void
    {
        $requiredStaticMethods = [
            'getByEmail',
            'createUser',
            'getTokenVersion',
            'verifyEmail',
            'initiatePasswordReset',
            'resetPassword',
            'incrementTokenVersion',
        ];

        $reflection = new \ReflectionClass(UserService::class);

        foreach ($requiredStaticMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "UserService must have method: {$method}()"
            );

            $methodReflection = $reflection->getMethod($method);

            $this->assertTrue(
                $methodReflection->isStatic(),
                "UserService::{$method}() must be static"
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
