<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->smallint('login_attempts')->default(0);
            $t->timestamp('locked_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropColumn('users', 'login_attempts');
        Schema::dropColumn('users', 'locked_until');
    }
};
