<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $t) {
            $t->id();
            $t->string('jti', 64)->unique();
            $t->bigint('user_id');
            $t->smallint('revoked')->default(0);
            $t->timestamp('expires_at');
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::drop('refresh_tokens');
    }
};
