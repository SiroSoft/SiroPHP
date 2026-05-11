<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->timestamp('email_verified_at')->nullable();
            $t->string('verification_token', 64)->nullable();
            $t->string('password_reset_token', 64)->nullable();
            $t->timestamp('password_reset_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropColumn('users', 'email_verified_at');
        Schema::dropColumn('users', 'verification_token');
        Schema::dropColumn('users', 'password_reset_token');
        Schema::dropColumn('users', 'password_reset_expires_at');
    }
};