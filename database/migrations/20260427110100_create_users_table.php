<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('email')->unique();
            $t->string('password');
            $t->smallint('status')->default(1);
            $t->integer('token_version')->default(1);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('users');
    }
};
