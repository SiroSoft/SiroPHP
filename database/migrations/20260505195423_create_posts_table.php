<?php

declare(strict_types=1);

use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body');
            $t->string('image', 255)->nullable();
            $t->string('locale', 5)->default('en');
            $t->string('status', 20)->default('draft');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('posts');
    }
};
