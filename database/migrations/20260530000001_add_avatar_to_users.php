<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('avatar', 500)->nullable();
            $t->string('phone', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('avatar');
            $t->dropColumn('phone');
        });
    }
};
